<?php
/**
 * SMS Line transport factory
 *
 * @author Maxim Petrovich <m.petrovich@artox.com>
 */

namespace ArtoxLab\Component\Notifier\Bridge\SmsLine;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

class SmsLineTransportFactory extends AbstractTransportFactory
{

    /**
     * Supported schemes
     *
     * @return array|string[]
     */
    protected function getSupportedSchemes(): array
    {
        return ['smsline'];
    }

    /**
     * Create
     *
     * @param Dsn $dsn DSN
     *
     * @return TransportInterface
     */
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme   = $dsn->getScheme();
        $login    = $this->getUser($dsn);
        $password = $this->getPassword($dsn);
        $from     = $dsn->getOption('from');
        $host     = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port     = $dsn->getPort();

        if ('smsline' === $scheme) {
            $transport = new SmsLineTransport($login, $password, $from, $this->client, $this->dispatcher);
            $transport->setHost($host);
            $transport->setPort($port);

            return $transport;
        }

        throw new UnsupportedSchemeException($dsn, 'smsline', $this->getSupportedSchemes());
    }

}
