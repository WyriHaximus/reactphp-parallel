<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use React\Promise\PromiseInterface;
use WyriHaximus\PoolInfo\PoolInfoInterface;

interface ThreadInterface
{
    public function execute(string $target, array $args = []);
}
