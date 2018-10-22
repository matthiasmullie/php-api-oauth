<?php

namespace MatthiasMullie\ApiOauth\Controllers\Authorize;

use MatthiasMullie\ApiOauth\Controllers\HtmlBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This is *not* an API endpoint, but an HTML form.
 * Authorization is not meant to be possible from an API endpoint,
 * because that would expose the actual password to the caller.
 * Instead, users who want to authorize an app access to their
 * data should be sent to this form that will allow them to log
 * in and then redirect to an application-specific location where
 * the application can obtain a token that can be exchanged for
 * an access token.
 */
class Base extends HtmlBase
{
    /**
     * @var string
     */
    protected $previousNonce;

    /**
     * @var string
     */
    protected $nonce;

    /**
     * {@inheritdoc}
     */
    public function invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): array
    {
        // we're going to have to start a session so that we can store the
        // nonce in there & compare it when the form is submitted
        // however... starting a session will require setting headers, which
        // won't work when running tests internally (via RequestHandler),
        // which is already printing output from other tests - in that case
        // we'll ignore starting the session...
        if (!headers_sent()) {
            session_start();
        }

        // fetch nonce from session & reset it
        $this->previousNonce = isset($_SESSION['nonce']) ? hash('sha512', $_SESSION['nonce']) : '';
        $_SESSION['nonce'] = $this->getRandom(session_id());
        $this->nonce = hash('sha512', $_SESSION['nonce']);

        return parent::invoke($request, $response, $args);
    }

    /**
     * @param string $email
     * @param string $error
     * @return string
     */
    public function getFormHtml(string $email = '', string $error = ''): string
    {
        return $this->parse(
            'authorize-form-html',
            ['error' => $error, 'email' => $email, 'nonce' => $this->nonce]
        );
    }
}
