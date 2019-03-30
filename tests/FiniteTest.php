<?php declare(strict_types=1);

namespace WyriHaximus\React\Tests\Parallel;

use React\EventLoop\Factory;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;
use WyriHaximus\React\Parallel\Finite;
use WyriHaximus\TestUtilities\TestCase;

/**
 * @internal
 */
final class FiniteTest extends TestCase
{
    use PoolInfoTestTrait;

    protected function poolFactory(): PoolInfoInterface
    {
        return new Finite(Factory::create(), 5);
    }
}
