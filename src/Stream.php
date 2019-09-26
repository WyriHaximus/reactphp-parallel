<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use parallel\Channel;
use parallel\Events;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use Rx\Observable;
use Rx\ObserverInterface;
use Throwable;

final class Stream
{
    /** @var LoopInterface */
    private $loop;

    /** @var Channel */
    private $input;

    /** @var Channel */
    private $output;

    /** @var Events */
    private $events;

    /** @var Communicator */
    private $communicator;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->input = Channel::make(\spl_object_hash($this) . '_input', Channel::Infinite);
        $this->output = Channel::make(\spl_object_hash($this) . '_output', Channel::Infinite);

        $this->communicator = new Communicator($this->input, $this->output);

        $this->events = new Events();
        $this->events->setTimeout(0);
        $this->events->addChannel($this->output);
    }

    public function send(Message $message): void
    {
        $this->input->send($message);
    }

    public function getCommunicator(): Communicator
    {
        return $this->communicator;
    }

    public function recv(): Observable
    {
        return Observable::create(function (ObserverInterface $observer): void {
            // Call 1K times per second
            $timer = $this->loop->addPeriodicTimer(0.001, function () use (&$timer, $observer): void {
                try {
                    while ($event = $this->events->poll()) {
                        switch ($event->type) {
                            case Events\Event\Type::Read:
                                $observer->onNext($event->value);
                                break;
                        }

                        /* $event->object removed
                            from Events when Event emitted */
                        $this->events->addChannel($event->object);
                    }
                } catch (Events\Error\Timeout $timeout) {
                    return;
                } catch (ClosingException $closed) {
                    return;
                } catch (Throwable $throwable) {
                    $observer->onError($throwable);
                    $this->loop->cancelTimer($timer);
                }
            });
        });
    }
}
