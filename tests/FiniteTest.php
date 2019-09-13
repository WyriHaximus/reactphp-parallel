<?php declare(strict_types=1);

namespace WyriHaximus\React\Tests\Parallel;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;
use WyriHaximus\React\Parallel\Finite;
use WyriHaximus\React\Parallel\PoolInterface;

/**
 * @internal
 */
final class FiniteTest extends AbstractPoolTest
{
    use PoolInfoTestTrait;

    protected function poolFactory(): PoolInfoInterface
    {
        return new Finite(Factory::create(), 5);
    }

    protected function createPool(LoopInterface $loop): PoolInterface
    {
        return new Finite($loop, 5);
    }
}
