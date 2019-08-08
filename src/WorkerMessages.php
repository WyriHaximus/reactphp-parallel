<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use parallel\Runtime;
use parallel\Channel;
use parallel\Future;
use parallel\Events;
use parallel\Events\Event;
use parallel\Events\Input;
use parallel\Events\Timeout;
use parallel\Channel\Closed;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Throwable;
use function WyriHaximus\React\futurePromise;

final class WorkerMessages
{
    public const INFO = 'info';
    public const CALL = 'call';
    public const RESULT = 'result';
    public const CLOSING = 'closing';
    public const CLOSED = 'closed';
}
