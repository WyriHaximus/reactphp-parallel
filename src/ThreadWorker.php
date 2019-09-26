<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use parallel\Channel\Errors\Closed;

final class ThreadWorker
{
    /** @var string */
    private $id;

    /** @var Communicator */
    private $communicator;

    /** @var ThreadInterface */
    private $thread;

    /** @var int */
    private $ticks = 0;

    /** @var float */
    private $startTime;

    private function __construct(string $id, string $threadClass, Communicator $communicator)
    {
        $this->id = $id;
        $this->communicator = $communicator;

        /** @var ThreadInterface $thread */
        $this->thread = new $threadClass();

        $this->startTime = \hrtime(true);
    }

    public static function execute(string $id, string $threadClass, Communicator $communicator): bool
    {
        return (new self($id, $threadClass, $communicator))->run();
    }

    public function run(): bool
    {
        $this->communicator->recv()->subscribe(function (Message $message): void {
            $this->handleInput($message);
        });

        return true;
    }

    private function handleInput(Message $message): void
    {
        if ($message->getType() === WorkerMessages::CALL) {
            $output = $message;//$this->thread->execute($input['target'], $input['args']);
            $this->communicator->send(new Message(
                WorkerMessages::RESULT,
                $this->id,
                $output,
                ''//,
//                $input['id']
            ));

            return;
        }

        if ($message->getType() === WorkerMessages::CLOSING) {
            $this->communicator->send(new Message(
                WorkerMessages::CLOSED,
                $this->id,
                null,
                ''
            ));

            throw new ClosingException();
        }
    }
}
