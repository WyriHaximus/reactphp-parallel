<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use parallel\Runtime;
use parallel\Channel;
use parallel\Future;
use parallel\Events;
use parallel\Events\Event;
use parallel\Events\Input;
use parallel\Events\Error\Timeout;
use parallel\Channel\Error\Closed;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Throwable;
use function WyriHaximus\React\futurePromise;

final class FiniteWorker
{
    /** @var LoopInterface */
    private $loop;

    /** @var Runtime[] */
    private $runtimes = [];

    /** @var array */
    private $queue;

    /** @var string */
    private $threadClass;

    /** @var Channel */
    private $inputChannel;

    /** @var Channel */
    private $outputChannel;

    /** @var Deferred[] */
    private $outstandingCalls = [];

    /** @var Events */
    private $events;

    /** @var Future[] */
    private $futures;

    public function __construct(LoopInterface $loop, int $threadCount, string $threadClass)
    {
        $this->loop = $loop;
        $this->queue = [];
        $this->threadClass = $threadClass;
        $this->inputChannel = Channel::make(spl_object_hash($this) . '_input', Channel::Infinite);
        $this->outputChannel = Channel::make(spl_object_hash($this) . '_output', Channel::Infinite);

        $autoload = \dirname(__FILE__) . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';
        foreach ([2, 5] as $level) {
            $autoload = \dirname(__FILE__, $level) . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';
            if (\file_exists($autoload)) {
                break;
            }
        }

        $this->events = new Events();
        $this->events->setTimeout(0);
        $this->events->addChannel($this->outputChannel);

        // Call 1K times per second
        $this->loop->addPeriodicTimer(0.001, function () {
            try {
                while ($event = $this->events->poll()) {
                    switch ($event->type) {
                        case Event\Type::Read:
                            $this->handleMessage($event->value);
                            break;
                    }

                    /* $event->object removed
                        from Events when Event emitted */
                    $this->events->addChannel($event->object);
                }
            } catch (Timeout $timeout) {
                return;
            } catch (ClosingException $closed) {
                return;
            } catch (Throwable $throwable) {
                echo $throwable->getMessage(), PHP_EOL;
            }
        });

        for ($i = 0; $i < $threadCount; $i++) {
            $runtime = new Runtime($autoload);
            //$id = bin2hex(random_bytes(8));
            $id = \spl_object_hash($runtime);
            $this->runtimes[$id] = [
                'id' => $id,
                'runtime' => $runtime,
                'output' => $this->outputChannel,
                'input' => $this->inputChannel,
                'threadClass' => $threadClass,
            ];
            futurePromise($loop, $this->runtimes[$id])->then(function (array $runtime) {
                $this->futures[] = $runtime['runtime']->run(function (string $id, string $threadClass, string $inputChannel, string $outputChannel) {
                    return ThreadWorker::execute($id, $threadClass, $inputChannel, $outputChannel);
                }, [$runtime['id'], $runtime['threadClass'], (string)$runtime['input'], (string)$runtime['output']]);
            })->done(null, function ($t) {
                echo (string)$t;
            });
        }
    }

    public function rpc(string $target, ...$args): PromiseInterface
    {
        $id = bin2hex(random_bytes(32));
        $deferred = new Deferred();
        $this->outstandingCalls[$id] = $deferred;
        $this->inputChannel->send([
            'id' => $id,
            'target' => $target,
            'args' => $args,
        ]);
        return $deferred->promise();
    }

    public function close(): void
    {
        $this->inputChannel->close();
        $this->outputChannel->close();
        foreach ($this->runtimes as $runtime) {
            $runtime['runtime']->close();
        }
    }

    public function kill(): void
    {
        $this->inputChannel->close();
        $this->outputChannel->close();
        foreach ($this->runtimes as $runtime) {
            $runtime['runtime']->kill();
        }
    }

    private function handleMessage(array $message): void
    {
        if ($message['message'] === WorkerMessages::RESULT) {
            $this->outstandingCalls[$message['id']]->resolve($message['output']);
        }

        if ($message['message'] === WorkerMessages::CLOSING) {
            $runtime = $this->runtimes[$message['id']];
            unset($this->runtimes[$message['id']]);
            $runtime['runtime']->close();
        }
    }
}
