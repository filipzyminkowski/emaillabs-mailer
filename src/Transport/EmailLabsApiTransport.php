<?php

namespace GlobeGroup\EmailLabsMailer\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Mateusz Kaczmarek <mateusz.kaczmarek@globegroup.pl>
 */
class EmailLabsApiTransport extends AbstractApiTransport
{
    private const ENDPOINT = 'https://api.emaillabs.net.pl/api/';

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
        return 'emaillabs+api://$EMAILLABS_APP_KEY:$EMAILLABS_SECRET@default?smtpAccount=$EMAILLABS_SMTP_ACCOUNT';
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $payload = $this->getPayload($sentMessage, $email, $envelope);

        $response = $this->client->request('POST', self::ENDPOINT.'new_sendmail', [
            'auth_basic' => [
                $this->appKey,
                $this->secretKey,
            ],
            'body' => $payload,
        ]);

        $result = json_decode($response->getContent(false));
        if (200 !== $response->getStatusCode()) {
            throw new HttpTransportException(sprintf('Unable to send an email: %s (code: %s).', $result->message, $result->code), $response);
        }

        $sentMessage->setMessageId($result->req_id);

        return $response;
    }

    private function getPayload(SentMessage $sentMessage, Email $email, Envelope $envelope): array
    {
        $payload = [
            'smtp_account' => $this->smtpAccount,
            'subject' => $email->getSubject(),

            'new_structure' => '1',

            'from' => $envelope->getSender()->getAddress(),

            'multi_cc' => '1',
            'multi_bcc' => '1',
        ];

        if ('' !== $envelope->getSender()->getName()) {
            $payload['from_name'] = $envelope->getSender()->getName();
        }

        $payload = $this->prepareRecipents($sentMessage, $email, $envelope, $payload);
        $payload = $this->prepareBody($email, $payload);
        $payload = $this->prepareAttachments($email, $payload);

        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'subject', 'content-type'];
        foreach ($email->getHeaders()->all() as $name => $header) {
            if (\in_array($name, $headersToBypass, true)) {
                continue;
            }
            $payload['headers'][] = $header->toString();
        }

        return $payload;
    }

    private function prepareAttachments(Email $email, array $payload): array
    {
        $attachments = $email->getAttachments();

        if (empty($attachments)) {
            return $payload;
        }

        /** @var DataPart $attachment */
        foreach ($attachments as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $disposition = $headers->getHeaderBody('Content-Disposition');

            $payload['files'][] = [
                'name' => $this->getNameFromContentTypeHeader($headers->get('Content-Type')->toString()),
                'mime' => $headers->get('Content-Type')->getBody(),
                'content' => $attachment->bodyToString(),
                'inline' => ('inline' === $disposition) ? 1 : 0,
            ];
        }

        return $payload;
    }

    private function getNameFromContentTypeHeader(string $header): string
    {
        if (preg_match('/name=(\S+)/', $header, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function prepareBody(Email $email, array $payload): array
    {
        if ($text = $email->getTextBody()) {
            $payload['text'] = $text;
        }

        if ($html = $email->getHtmlBody()) {
            $payload['html'] = $html;
        }

        return $payload;
    }

    private function prepareRecipents(SentMessage $sentMessage, Email $email, Envelope $envelope, array $payload): array
    {
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

        /** @var Address $bcc */
        foreach ($email->getCc() as $bcc) {
            $payload['bcc'][$bcc->getAddress()] = $bcc->getName();
        }

        return $payload;
    }
}
