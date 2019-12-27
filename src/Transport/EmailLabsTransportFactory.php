<?php

namespace GlobeGroup\EmailLabsMailer\Transport;

use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * @author Mateusz Kaczmarek <mateusz.kaczmarek@globegroup.pl>
 */
final class EmailLabsTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $appKey = $dsn->getUser();
        $secretKey = $dsn->getPassword();
        $smtpAccount = $dsn->getOption('smtpAccount');

        if (!$smtpAccount) {
            throw new IncompleteDsnException('Option "smtpAccount" is required for emaillabs transport.');
        }

        if ('emaillabs+api' === $scheme) {
            return new EmailLabsApiTransport($appKey, $secretKey, $smtpAccount, $this->client, $this->dispatcher, $this->logger);
        }

        throw new UnsupportedSchemeException($dsn, 'emaillabs', $this->getSupportedSchemes());
    }

    public function supports(Dsn $dsn): bool
    {
        return 'emaillabs+api' === $dsn->getScheme();
    }

    protected function getSupportedSchemes(): array
    {
        return [
            'emaillabs+api',
        ];
    }
}
