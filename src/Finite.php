<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use Closure;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use WyriHaximus\PoolInfo\Info;
use function WyriHaximus\React\futurePromise;

final class Finite implements PoolInterface
{
    /** @var LoopInterface */
    private $loop;

    /** @var Runtime[] */
    private $runtimes = [];

    /** @var string[] */
    private $idleRuntimes = [];

    /** @var array */
    private $queue;

    /** @var FutureToPromiseConverter */
    private $futureConverter;

    /**
     * @param LoopInterface $loop
     * @param int           $threadCount
     */
    public function __construct(LoopInterface $loop, int $threadCount)
    {
        $this->loop = $loop;
        $this->queue = [];

        $autoload = \dirname(__FILE__) . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';
        foreach ([2, 5] as $level) {
            $autoload = \dirname(__FILE__, $level) . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';
            if (\file_exists($autoload)) {
                break;
            }
        }

        $this->futureConverter = new FutureToPromiseConverter($loop);
        for ($i = 0; $i < $threadCount; $i++) {
            $runtime = new Runtime($this->futureConverter, $autoload);
            $this->runtimes[\spl_object_hash($runtime)] = $runtime;
        }
        $this->idleRuntimes = \array_keys($this->runtimes);
    }

    public function run(Closure $callable, array $args = []): PromiseInterface
    {
        return futurePromise($this->loop)->then(function () {
            return new Promise(function ($resolve, $reject): void {
                if (\count($this->idleRuntimes) === 0) {
                    $this->queue[] = [$resolve, $reject];

                    return;
                }

                $resolve($this->getIdleRuntime());
            });
        })->then(function (Runtime $runtime) use ($callable, $args) {
            return $runtime->run($callable, $args)->always(function () use ($runtime): void {
                $this->addRuntimeToIdleList($runtime);
                $this->progressQueue();
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
        yield Info::BUSY => \count($this->runtimes) - \count($this->idleRuntimes);
        yield Info::CALLS => \count($this->queue);
        yield Info::IDLE  => \count($this->idleRuntimes);
        yield Info::SIZE  => \count($this->runtimes);
    }

    private function getIdleRuntime(): Runtime
    {
        return $this->runtimes[\array_pop($this->idleRuntimes)];
    }

    private function addRuntimeToIdleList(Runtime $runtime): void
    {
        $this->idleRuntimes[] =\spl_object_hash($runtime);
    }

    private function progressQueue(): void
    {
        if (\count($this->queue) === 0) {
            return;
        }

        [$resolve, $reject] = \array_pop($this->queue);
        try {
            $resolve($this->getIdleRuntime());
        } catch (\Throwable $throwable) {
            $reject($throwable);
        }
    }
}
