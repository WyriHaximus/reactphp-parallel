<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use Closure;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use WyriHaximus\PoolInfo\Info;

final class Infinite implements LowLevelPoolInterface
{
    /** @var LoopInterface */
    private $loop;

    /** @var Runtime[] */
    private $runtimes = [];

    /** @var string[] */
    private $idleRuntimes = [];

    /** @var TimerInterface[] */
    private $ttlTimers = [];

    /** @var FutureToPromiseConverter */
    private $futureConverter;

    /** @var string */
    private $autoload;

    /** @var float */
    private $ttl;

    /** @var Group[] */
    private $groups = [];

    /**
     * @param LoopInterface $loop
     * @param float         $ttl
     */
    public function __construct(LoopInterface $loop, float $ttl)
    {
        $this->loop = $loop;
        $this->ttl = $ttl;
        $this->autoload = \dirname(__FILE__) . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';
        foreach ([2, 5] as $level) {
            $this->autoload = \dirname(__FILE__, $level) . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';
            if (\file_exists($this->autoload)) {
                break;
            }
        }

        $this->futureConverter = new FutureToPromiseConverter($loop);
    }

    public function run(Closure $callable, array $args = []): PromiseInterface
    {
        return (new Promise(function ($resolve, $reject): void {
            if (\count($this->idleRuntimes) === 0) {
                $resolve($this->spawnRuntime());

                return;
            }

            $resolve($this->getIdleRuntime());
        }))->then(function (Runtime $runtime) use ($callable, $args) {
            return $runtime->run($callable, $args)->always(function () use ($runtime): void {
                if ($this->ttl >= 0.1) {
                    $this->addRuntimeToIdleList($runtime);
                    $this->startTtlTimer($runtime);

                    return;
                }

                $this->closeRuntime(\spl_object_hash($runtime));
            });
        });
    }

    public function close(): void
    {
        if (count($this->groups) > 0) {
            return;
        }

        foreach ($this->runtimes as $hash => $runtime) {
            $this->closeRuntime($hash);
        }
    }

    public function kill(): void
    {
        if (count($this->groups) > 0) {
            return;
        }

        foreach ($this->runtimes as $runtime) {
            $runtime->kill();
        }
    }

    public function info(): iterable
    {
        yield Info::TOTAL => \count($this->runtimes);
        yield Info::BUSY => \count($this->runtimes) - \count($this->idleRuntimes);
        yield Info::CALLS => 0;
        yield Info::IDLE  => \count($this->idleRuntimes);
        yield Info::SIZE  => \count($this->runtimes);
    }

    public function acquireGroup(): Group
    {
        $group = Group::create();
        $this->groups[(string)$group] = $group;

        return $group;
    }

    public function releaseGroup(Group $group): void
    {
        unset($this->groups[(string)$group]);
    }

    private function getIdleRuntime(): Runtime
    {
        /** @var string $hash */
        $hash = \array_pop($this->idleRuntimes);

        if (\array_key_exists($hash, $this->ttlTimers)) {
            $this->loop->cancelTimer($this->ttlTimers[$hash]);
            unset($this->ttlTimers[$hash]);
        }

        return $this->runtimes[$hash];
    }

    private function addRuntimeToIdleList(Runtime $runtime): void
    {
        $hash = \spl_object_hash($runtime);
        $this->idleRuntimes[$hash] = $hash;
    }

    private function spawnRuntime(): Runtime
    {
        $runtime = new Runtime($this->futureConverter, $this->autoload);
        $this->runtimes[\spl_object_hash($runtime)] = $runtime;

        return $runtime;
    }

    private function startTtlTimer(Runtime $runtime): void
    {
        $hash = \spl_object_hash($runtime);

        $this->ttlTimers[$hash] = $this->loop->addTimer($this->ttl, function () use ($hash): void {
            $this->closeRuntime($hash);
        });
    }

    private function closeRuntime(string $hash): void
    {
        $runtime = $this->runtimes[$hash];
        $runtime->close();

        unset($this->runtimes[$hash]);

        if (\array_key_exists($hash, $this->idleRuntimes)) {
            unset($this->idleRuntimes[$hash]);
        }
        if (\array_key_exists($hash, $this->ttlTimers)) {
            $this->loop->cancelTimer($this->ttlTimers[$hash]);
            unset($this->ttlTimers[$hash]);
        }
    }
}
