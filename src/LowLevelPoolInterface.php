<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

interface LowLevelPoolInterface extends PoolInterface
{
    public function acquireGroup(): Group;

    public function releaseGroup(Group $group): void;
}
