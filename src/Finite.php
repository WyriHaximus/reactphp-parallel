<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use Closure;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use WyriHaximus\PoolInfo\Info;
use function React\Promise\reject;

final class Finite implements PoolInterface
{
    /** @var PoolInterface */
    private $pool;

    /** @var int */
    private $threadCount;

    /** @var int */
    private $idleRuntimes;

    /** @var array */
    private $queue;

    /** @var GroupInterface|null */
    private $group;

    /** @var bool */
    private $closed = false;

    public static function create(LoopInterface $loop, int $threadCount): self
    {
        return new self(new Infinite($loop, 1), $threadCount);
    }

    public static function createWithPool(PoolInterface $pool, int $threadCount): self
    {
        return new self($pool, $threadCount);
    }

    /**
     * @param PoolInterface $pool
     * @param int           $threadCount
     */
    private function __construct(PoolInterface $pool, int $threadCount)
    {
        $this->pool = $pool;
        $this->threadCount = $threadCount;
        $this->idleRuntimes = $threadCount;
        $this->queue = [];

        if ($this->pool instanceof LowLevelPoolInterface) {
            $this->group = $this->pool->acquireGroup();
        }
    }

    public function run(Closure $callable, array $args = []): PromiseInterface
    {
        if ($this->closed === true) {
            return reject(ClosedException::create());
        }

        return (new Promise(function ($resolve, $reject): void {
            if ($this->idleRuntimes === 0) {
                $this->queue[] = [$resolve, $reject];

                return;
            }

            $resolve();
        }))->then(function () use ($callable, $args) {
            $this->idleRuntimes--;
            return $this->pool->run($callable, $args)->always(function (): void {
                $this->idleRuntimes++;
                $this->progressQueue();
            });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function close(): bool
    {
        $this->closed = true;

        if ($this->pool instanceof LowLevelPoolInterface) {
            $this->pool->releaseGroup($this->group);
        }

         $this->pool->close();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function kill(): bool
    {
        $this->closed = true;

        if ($this->pool instanceof LowLevelPoolInterface) {
            $this->pool->releaseGroup($this->group);
        }

        $this->pool->kill();

        return true;
    }

    public function info(): iterable
    {
        yield Info::TOTAL => $this->threadCount;
        yield Info::BUSY => $this->threadCount - $this->idleRuntimes;
        yield Info::CALLS => \count($this->queue);
        yield Info::IDLE  => $this->idleRuntimes;
        yield Info::SIZE  => $this->threadCount;
    }

    private function progressQueue(): void
    {
        if (\count($this->queue) === 0) {
            return;
        }

        [$resolve, $reject] = \array_pop($this->queue);
        try {
            $resolve();
        } catch (\Throwable $throwable) {
            $reject($throwable);
        }
    }
}
