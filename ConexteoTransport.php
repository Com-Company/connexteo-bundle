<?php

namespace Symfony\Component\Notifier\Bridge\OvhCloud;

use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ConexteoTransport extends AbstractTransport
{
    protected const HOST = 'api.conexteo.com';

    private ?string $sender = null;

    public function __construct(private readonly string $appId, private readonly string $apiKey, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($client, $dispatcher);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $endpoint = sprintf('https://%s/messages', $this->getEndpoint());

        $content = [
            "message_type" => 1,
            "content" => $message->getSubject(),
            "recipients" => [$message->getPhone()],
        ];

        if ($this->sender) {
            $content['sender'] = $this->sender;
        }

        $headers["X-APP-ID"] = $this->appId;
        $headers["X-API-KEY"] = $this->apiKey;
        $headers['Content-Type'] = 'application/json';

        $response = $this->client->request('POST', $endpoint, [
            'headers' => $headers,
            'json' => $content
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote OvhCloud server.', $response, 0, $e);
        }

        if (200 !== $statusCode) {
            $error = $response->toArray(false);

            throw new TransportException(sprintf('Unable to send the SMS: %s.', $error['message']), $response);
        }

        $success = $response->toArray(false);

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($success['message_id']);

        return $sentMessage;
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    public function __toString(): string
    {
        if (null !== $this->sender) {
            return sprintf('conexteo://%s?&sender=%s', $this->getEndpoint(),  $this->sender);
        }

        return sprintf('conexteo://%s', $this->getEndpoint());
    }

    public function setSender(?string $sender): static
    {
        $this->sender = $sender;

        return $this;
    }
}