<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

final class WorkerMessages
{
    public const INFO = 'info';
    public const CALL = 'call';
    public const RESULT = 'result';
    public const CLOSING = 'closing';
    public const CLOSED = 'closed';
}
