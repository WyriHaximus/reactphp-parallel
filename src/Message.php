<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

final class Message
{
    /** @var string */
    private $type;

    /** @var string */
    private $id;

    /** @var mixed */
    private $payload;

    /** @var string */
    private $replyTo;

    /**
     * @param string $type
     * @param string $id
     * @param mixed $payload
     * @param string $replyTo
     */
    public function __construct(string $type, string $id, $payload, string $replyTo)
    {
        $this->type = $type;
        $this->id = $id;
        $this->payload = $payload;
        $this->replyTo = $replyTo;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }
}
