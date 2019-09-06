<?php declare(strict_types=1);

namespace WyriHaximus\React\Tests\Parallel;

use Money\Money;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\Parallel\PoolInterface;
use function React\Promise\all;

/**
 * @internal
 */
abstract class AbstractPoolTest extends AsyncTestCase
{
    abstract protected function createPool(LoopInterface $loop): PoolInterface;

    public function provideCallablesAndTheirExpectedResults()
    {
        yield 'math' => [
            function (int ...$ints) {
                $result = 0;

                foreach ($ints as $int) {
                    $result += $int;
                }

                return $result;
            },
            [
                1,
                2,
                3,
            ],
            6,
        ];

        yield 'money-same-currency' => [
            function (Money $euro, Money $usd) {
                return $euro->isSameCurrency($usd);
            },
            [
                Money::EUR(512),
                Money::USD(512),
            ],
            false,
        ];

        yield 'money-add' => [
            function (Money ...$euros) {
                $total = Money::EUR(0);

                foreach ($euros as $euro) {
                    $total = $total->add($euro);
                }

                return (int)$total->getAmount();
            },
            [
                Money::EUR(512),
                Money::EUR(512),
            ],
            1024,
        ];

        yield 'sleep' => [
            function () {
                sleep(1);

                return true;
            },
            [],
            true,
        ];
    }

    /**
     * @dataProvider provideCallablesAndTheirExpectedResults
     */
    public function testFullRunThrough(callable $callable, array $args, $expectedResult)
    {
        $loop = Factory::create();
        $pool = $this->createPool($loop);

        $promise = $pool->run($callable, $args)->always(function () use ($pool) {
            $pool->close();
        });
        $result = $this->await($promise, $loop);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider provideCallablesAndTheirExpectedResults
     */
    public function testFullRunThroughMultipleConsecutiveCalls(callable $callable, array $args, $expectedResult)
    {
        $loop = Factory::create();
        $pool = $this->createPool($loop);

        $promises = [];
        foreach (range(0, 8) as $i) {
            $promises[$i] = $pool->run($callable, $args);
        }
        $results = $this->await(all($promises)->always(function () use ($pool) {
            $pool->close();
        }), $loop);

        foreach ($results as $result) {
            self::assertSame($expectedResult, $result);
        }
    }
}
