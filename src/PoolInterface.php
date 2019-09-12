<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use Closure;
use React\Promise\PromiseInterface;
use WyriHaximus\PoolInfo\PoolInfoInterface;

interface PoolInterface extends PoolInfoInterface
{
    /**
     * @param Closure $callable
     * @param mixed[] $args
     * @return PromiseInterface
     */
    public function run(Closure $callable, array $args = []): PromiseInterface;

    /**
     * Gently close every thread in the pool.
     */
    public function close(): void;

    /**
     * Kill every thread in the pool.
     */
    public function kill(): void;
}
