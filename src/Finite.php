<?php

namespace WyriHaximus\React\Parallel;

use ArrayIterator;
use InfiniteIterator;
use Iterator;
use parallel\Exception;
use parallel\Runtime;
use parallel\Future;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function WyriHaximus\React\futurePromise;

final class Finite
{
    /** @var LoopInterface */
    private $loop;

    /** @var Iterator */
    private $iterator;

    /** @var Runtime[] */
    private $runtimes = [];

    /**
     * @param LoopInterface $loop
     * @param int $threadCount
     */
    public function __construct(LoopInterface $loop, int $threadCount)
    {
        $this->loop = $loop;

        $autoload = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        for ($i = 0; $i < $threadCount; $i++) {
            $this->runtimes[] = new Runtime($autoload);
        }

        $this->iterator = new InfiniteIterator(new ArrayIterator($this->runtimes));
    }

    public function run(callable $callable, array $args = []): PromiseInterface
    {
        return futurePromise($this->loop)->then(function () use ($callable, $args) {
            return new Promise(function ($resolve, $reject) use ($callable, $args) {
                $this->iterator->next();
                $future = $this->iterator->current()->run($callable, $args);
                $timer = $this->loop->addPeriodicTimer(0.001, function () use (&$timer, $future, $resolve, $reject) {
                    if ($future->done() === false) {
                        return;
                    }

                    $this->loop->cancelTimer($timer);
                    try {
                        $resolve($future->value());
                    } catch (\Throwable $throwable) {
                        $reject($throwable);
                    }
                });
            });
        });
    }

    public function close()
    {
        foreach ($this->runtimes as $runtime) {
            $runtime->close();
        }
    }

    public function kill()
    {
        foreach ($this->runtimes as $runtime) {
            $runtime->kill();
        }
    }
}
