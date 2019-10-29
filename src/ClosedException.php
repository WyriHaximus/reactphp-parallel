<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

final class ClosedException extends \Exception
{
    public static function create(): self
    {
        return new self('Pool is closed and won\'t run your Closure');
    }
}
