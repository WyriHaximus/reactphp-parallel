<?php declare(strict_types=1);

namespace WyriHaximus\React\Tests\Parallel;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;
use WyriHaximus\React\Parallel\Finite;
use WyriHaximus\React\Parallel\Infinite;
use WyriHaximus\React\Parallel\PoolInterface;

/**
 * @internal
 */
final class FiniteWithPoolTest extends AbstractPoolTest
{
    use PoolInfoTestTrait;

    protected function poolFactory(): PoolInfoInterface
    {
        return Finite::createWithPool(new Infinite(Factory::create(), 0.2), 5);
    }

    protected function createPool(LoopInterface $loop): PoolInterface
    {
        return Finite::createWithPool(new Infinite($loop, 0.2), 5);
    }
}
