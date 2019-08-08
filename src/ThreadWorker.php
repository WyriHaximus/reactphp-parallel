<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use parallel\Runtime;
use parallel\Channel;
use parallel\Future;
use parallel\Events;
use parallel\Events\Event;
use parallel\Events\Input;
use parallel\Events\Timeout;
use parallel\Channel\Closed;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Throwable;
use function WyriHaximus\React\futurePromise;

final class ThreadWorker
{
    /** @var string */
    private $id;

    /** @var Channel */
    private $inputChannel;

    /** @var Channel */
    private $outputChannel;

    /** @var ThreadInterface */
    private $thread;

    /** @var int */
    private $ticks = 0;

    /** @var float */
    private $startTime;

    public static function execute(string $id, string $threadClass, string $inputChannel, string $outputChannel): bool
    {
        return (new self($id, $threadClass, $inputChannel, $outputChannel))->run();
    }

    private function __construct(string $id, string $threadClass, string $inputChannel, string $outputChannel)
    {
        $this->id = $id;
        $this->inputChannel = Channel::open($inputChannel);
        $this->outputChannel = Channel::open($outputChannel);

        /** @var ThreadInterface $thread */
        $this->thread = new $threadClass();

        $this->startTime = hrtime(true);
    }

    public function run(): bool
    {
        $this->sendInfo();

        try {
            while ($input = $this->inputChannel->recv()) {
                $this->ticks++;
                $this->handleInput($input);
                $this->sendInfo();
            }
            $this->sendInfo();
        } catch (Closed $closed) {
            //
        } catch (ClosingException $closed) {
            //
        }
        unset($threadClass);

        return true;
    }

    private function sendInfo()
    {
        $this->outputChannel->send([
            'message' => WorkerMessages::INFO,
            'id' => $this->id,
            'ticks' => $this->ticks,
            'uptime' => hrtime(true) - $this->startTime,
            'memory' => [
                'external' => \memory_get_usage(true),
                'external_peak' => \memory_get_peak_usage(true),
                'internal' => \memory_get_usage(),
                'internal_peak' => \memory_get_peak_usage(),
            ],
        ]);
    }

    private function handleInput(array $input)
    {
        if ($input['message'] === WorkerMessages::CALL) {
            $output = $this->thread->execute($input['target'], $input['args']);
            $this->outputChannel->send([
                'message' => WorkerMessages::RESULT,
                'id' => $input['id'],
                'handling_by' => $this->id,
                'output' => $output,
            ]);

            return;
        }

        if ($input['message'] === WorkerMessages::CLOSING) {
            $this->outputChannel->send([
                'message' => WorkerMessages::CLOSED,
                'id' => $this->id,
            ]);

            throw new ClosingException();
        }
    }
}
