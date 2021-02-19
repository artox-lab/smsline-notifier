<?php
/**
 * Sms line transport
 *
 * @author Maxim Petrovich <m.petrovich@artox.com>
 */
namespace  ArtoxLab\Component\Notifier\Bridge\SmsLine;

use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SmsLineTransport extends AbstractTransport
{

    public const HOST = 'api.smsline.by';

    /**
     * Login
     *
     * @var string
     */
    private string $login;

    /**
     * Password
     *
     * @var string
     */
    private string $password;

    /**
     * Sender name
     *
     * @var string
     */
    private string $from;

    /**
     * SmsLineTransport constructor.
     *
     * @param string                        $login      Login
     * @param string                        $password   Password
     * @param string                        $from       Sender name
     * @param HttpClientInterface|null      $client     Http client
     * @param EventDispatcherInterface|null $dispatcher Event dispatcher
     */
    public function __construct(
        string $login,
        string $password,
        string $from,
        HttpClientInterface $client = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        $this->login    = $login;
        $this->password = $password;
        $this->from     = $from;

        parent::__construct($client, $dispatcher);
    }

    /**
     * Send message
     *
     * @param MessageInterface $message Message
     *
     * @return SentMessage
     */
    protected function doSend(MessageInterface $message): SentMessage
    {
        if (false === $message instanceof SmsMessage) {
            throw new LogicException(sprintf('The "%s" transport only supports instances of "%s" (instance of "%s" given).', __CLASS__, SmsMessage::class, get_debug_type($message)));
        }

        $requestBody = [
            'target' => $this->from,
            'msisdn' => preg_replace('/[^\d]/', '', $message->getPhone()),
            'text'   => $message->getSubject(),
        ];

        $endpoint = sprintf('https://%s/v3/messages/single/sms', $this->getEndpoint());
        $response = $this->client->request(
            'POST',
            $endpoint,
            [
                'headers' => [
                    'Content-Type'       => 'application/json',
                    'Authorization-User' => $this->login,
                    'Authorization'      => 'Bearer ' . $this->buildRequestSignature($requestBody),
                ],
                'json'    => $requestBody,
            ]
        );

        if (200 !== $response->getStatusCode()) {
            $error = $response->toArray(false)['error'];

            throw new TransportException(
                'Unable to send the SMS: ' . $error['message'] . sprintf(' (see %s).', $error['code']),
                $response
            );
        }

        return new SentMessage($message, (string) $this);
    }

    /**
     * Supports
     *
     * @param MessageInterface $message Message
     *
     * @return bool
     */
    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('smsline://%s?from=%s', $this->getEndpoint(), $this->from);
    }

    /**
     * Build request signature
     *
     * @param array $requestBody Request body
     *
     * @return string
     */
    private function buildRequestSignature(array $requestBody): string
    {
        return hash_hmac('sha256', 'messagessinglesms' . json_encode($requestBody), $this->password);
    }

}
