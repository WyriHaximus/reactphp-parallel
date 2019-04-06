<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use ArrayIterator;
use InfiniteIterator;
use Iterator;
use parallel\Runtime;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use WyriHaximus\PoolInfo\Info;
use function WyriHaximus\React\futurePromise;

final class Finite implements PoolInterface
{
    /** @var LoopInterface */
    private $loop;

    /** @var Iterator */
    private $iterator;

    /** @var Runtime[] */
    private $runtimes = [];

    /**
     * @param LoopInterface $loop
     * @param int           $threadCount
     */
    public function __construct(LoopInterface $loop, int $threadCount)
    {
        $this->loop = $loop;

        $autoload = \dirname(__FILE__) . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';
        foreach ([2, 5] as $level) {
            $autoload = \dirname(__FILE__, $level) . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';
            if (\file_exists($autoload)) {
                break;
            }
        }

        for ($i = 0; $i < $threadCount; $i++) {
            $this->runtimes[] = new Runtime($autoload);
        }

        $this->iterator = new InfiniteIterator(new ArrayIterator($this->runtimes));
    }

    public function run(callable $callable, array $args = []): PromiseInterface
    {
        return futurePromise($this->loop)->then(function () use ($callable, $args) {
            return new Promise(function ($resolve, $reject) use ($callable, $args): void {
                $this->iterator->next();
                $future = $this->iterator->current()->run($callable, $args);
                /** @var TimerInterface $timer */
                $timer = $this->loop->addPeriodicTimer(0.001, function () use (&$timer, $future, $resolve, $reject): void {
                    if ($future->done() === false) {
                        return;
                    }

                    if ($timer instanceof TimerInterface) {
                        $this->loop->cancelTimer($timer);
                    }
                    try {
                        $resolve($future->value());
                    } catch (\Throwable $throwable) {
                        $reject($throwable);
                    }
                });
            });
        });
    }

    public function close(): void
    {
        foreach ($this->runtimes as $runtime) {
            $runtime->close();
        }
    }

    public function kill(): void
    {
        foreach ($this->runtimes as $runtime) {
            $runtime->kill();
        }
    }

    public function info(): iterable
    {
        yield Info::TOTAL => \count($this->runtimes);
        yield Info::BUSY => \count($this->runtimes);
        yield Info::CALLS => 0;
        yield Info::IDLE  => 0;
        yield Info::SIZE  => \count($this->runtimes);
    }
}
