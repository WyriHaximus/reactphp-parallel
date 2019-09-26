<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

final class Group
{
    /** @var string */
    private $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function create(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
