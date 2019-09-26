<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use React\Promise\PromiseInterface;

final class Worker
{
    /** @var PoolInterface */
    private $pool;

    /** @var ThreadInterface */
    private $thread;

    public function __construct(PoolInterface $pool, ThreadInterface $thread)
    {
        $this->pool = $pool;
        $this->thread = $thread;
    }

    /**
     * @param string $target
     * @param mixed[] $args
     *
     * @return PromiseInterface
     */
    public function execute(string $target, array $args = []): PromiseInterface
    {
    }
}
