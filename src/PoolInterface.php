<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use React\Promise\PromiseInterface;
use WyriHaximus\PoolInfo\PoolInfoInterface;

interface PoolInterface extends PoolInfoInterface
{
    public function run(callable $callable, array $args = []): PromiseInterface;
}
