<?php

namespace GlobeGroup\EmaillabsMailer\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Mateusz Kaczmarek <mateusz.kaczmarek@globegroup.pl>
 */
class EmaillabsApiTransport extends AbstractApiTransport
{
    private const ENDPOINT = 'api.emaillabs.net.pl';

    private $appKey;
    private $secretKey;
    private $smtpAccount;

    public function __construct(string $appKey, string $secretKey, $smtpAccount, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;
        $this->smtpAccount = $smtpAccount;

        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString(): string
    {
        // TODO: change this xd
        return 'emaillabs+api://';
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $payload = $this->getPayload($sentMessage, $email, $envelope);

        $response = $this->client->request('POST', self::ENDPOINT, [
            'auth_basic' => [$this->appKey, $this->secretKey],
            'body' => $payload,
        ]);

        $result = json_decode($response->getContent(false));
        if (200 !== $response->getStatusCode()) {
            dump($result);
            throw new HttpTransportException(sprintf('Unable to send an email: %s (code: %s).', $result->message, $result->code), $response);
        }

        $sentMessage->setMessageId($result->req_id);

        return $response;
    }

    private function getPayload(SentMessage $sentMessage, Email $email, Envelope $envelope): array
    {
        $payload = [
            'smtp_account' => $this->smtpAccount,
            // TODO: walidacja na max 128 znaków
            'subject' => $email->getSubject(), // max 128 znaków

            'new_structure' => '1',

            'from' => $envelope->getSender()->getAddress(),
            'from_name' => $envelope->getSender()->getName(),

// TODO: check if required
//            'headers' => '',
            'multi_cc' => '1',
            'multi_bcc' => '1',

            // TODO: only one email :(
            'reply_to' => $email->getReplyTo()[0]->getAddress(),

// TODO: check if required
//            'tags' => '',

                // TODO: add support for attachments
//            'files' => '',
//            'files[name]' => '',
//            'files[mime]' => '',
//            'files[content]' => '',
        ];

        /** @var Address $recipient */
        foreach ($this->getRecipients($email, $envelope) as $recipient) {
            $payload['to'][] = [
                'email' => $recipient->getAddress(),
                'reciver_name' => $recipient->getName(),
                'message_id' => $sentMessage->getMessageId(),
            ];
        }

        /** @var Address $cc */
        foreach ($email->getCc() as $cc) {
            $payload['cc'][$cc->getAddress()] = $cc->getName();
        }

        /** @var Address $cc */
        foreach ($email->getCc() as $bcc) {
            $payload['bcc'][$bcc->getAddress()] = $bcc->getName();
        }

        if ($text = $email->getTextBody()) {
            $payload['text'] = $text ;
        }

        if ($html = $email->getHtmlBody()) {
            $payload['html'] = $html;
        }

        return $payload;
    }
}
