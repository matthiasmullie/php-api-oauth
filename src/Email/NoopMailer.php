<?php

namespace MatthiasMullie\ApiOauth\Email;

use Psr\Http\Message\ResponseInterface;
use Stampie\Mailer;
use Stampie\MessageInterface;

class NoopMailer extends Mailer
{
    /**
     * {@inheritdoc}
     */
    public function send(MessageInterface $message)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function getEndpoint()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function handle(ResponseInterface $response)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function format(MessageInterface $message)
    {
        return '';
    }
}
