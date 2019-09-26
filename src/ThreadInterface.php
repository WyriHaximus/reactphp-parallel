<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

/**
 * Worker class that runs inside a thread.
 */
interface ThreadInterface
{
    /**
     * @param string $target
     * @param mixed[] $args
     *
     * @return mixed
     */
    public function execute(string $target, array $args = []);
}
