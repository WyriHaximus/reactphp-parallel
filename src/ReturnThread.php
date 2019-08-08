<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;


final class ReturnThread implements ThreadInterface
{
    public function execute(string $target, array $args = [])
    {
        return [
            'target' => $target,
            'args' => $args,
        ];
    }
}