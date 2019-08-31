<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use parallel\Runtime as ParallelRuntime;
use React\Promise\PromiseInterface;

final class Runtime
{
    /** @var string */
    private $id;

    /** @var ParallelRuntime */
    private $runtime;

    /** @var FutureToPromiseConverter */
    private $futureToPromiseConverter;

    public function __construct(FutureToPromiseConverter $futureToPromiseConverter, string $autoload)
    {
        $this->runtime = new ParallelRuntime($autoload);
        $this->id = spl_object_hash($this->runtime);
        $this->futureToPromiseConverter = $futureToPromiseConverter;
    }

    public function run(callable $callable, ...$args): PromiseInterface
    {
        return $this->futureToPromiseConverter->convert(
            $this->runtime->run($callable, $args)
        );
    }

    public function close()
    {
        $this->runtime->close();
    }

    public function kill()
    {
        $this->runtime->kill();
    }
}
