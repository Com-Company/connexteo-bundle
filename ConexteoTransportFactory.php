<?php

namespace Symfony\Component\Notifier\Bridge\OvhCloud;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

final class ConexteoTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): ConexteoTransport
    {
        $scheme = $dsn->getScheme();

        if ('conexteo' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'conexteo', $this->getSupportedSchemes());
        }

        $appId = $this->getUser($dsn);
        $apiKey = $this->getPassword($dsn);
        $sender = $dsn->getOption('sender');
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return (new ConexteoTransport($appId, $apiKey, $this->client, $this->dispatcher))->setHost($host)->setPort($port)->setSender($sender);
    }

    protected function getSupportedSchemes(): array
    {
        return ['ovhcloud'];
    }
}