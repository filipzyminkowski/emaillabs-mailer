<?php

namespace GlobeGroup\EmaillabsMailer\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * @author Mateusz Kaczmarek <mateusz.kaczmarek@globegroup.pl>
 */
final class EmaillabsTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $appKey = $dsn->getUser();
        $secretKey = $dsn->getPassword();
        $stmpAccount = $dsn->getOption('smtpAccount');

        if ('emaillabs+api' === $scheme) {
            return new EmaillabsApiTransport($appKey, $secretKey, $stmpAccount, $this->client, $this->dispatcher, $this->logger);
        }

        throw new UnsupportedSchemeException($dsn, 'emaillabs', $this->getSupportedSchemes());
    }
    public function supports(Dsn $dsn): bool
    {
        return 'emaillabs+api' === $dsn->getScheme();
    }

    protected function getSupportedSchemes(): array
    {
        return ['emaillabs+api'];
    }
}
