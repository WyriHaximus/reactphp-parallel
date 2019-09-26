<?php declare(strict_types=1);

namespace WyriHaximus\React\Parallel;

use parallel\Channel;
use parallel\Channel\Error\Closed;
use Rx\Observable;
use Rx\ObserverInterface;

final class Communicator
{
    /** @var Channel */
    private $input;

    /** @var Channel */
    private $output;

    public function __construct(Channel $input, Channel $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function send(Message $message): void
    {
        $this->output->send($message);
    }

    public function recv(): Observable
    {
        return Observable::create(function (ObserverInterface $observer): void {
            try {
                while ($input = $this->input->recv()) {
                    $observer->onNext($input);
                }
            } catch (Closed $closed) {
                // @ignoreException
            } catch (ClosingException $closed) {
                // @ignoreException
            }

            $observer->onCompleted();
        });
    }
}
