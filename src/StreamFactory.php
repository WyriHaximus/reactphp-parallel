<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use React\EventLoop\LoopInterface;

final class StreamFactory
{
    /** @var LoopInterface */
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function create(): Stream
    {
        return new Stream($this->loop);
    }
}
