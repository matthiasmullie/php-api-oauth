<?php

namespace MatthiasMullie\ApiOauth\Controllers\Authorize;

use League\Route\Http\Exception;
use PDO;
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
class Base extends \MatthiasMullie\ApiOauth\Controllers\Base
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        try {
            $result = $this->invoke($request, $response, $args);
        } catch (Exception $e) {
            $result = [
                'status_code' => $e->getStatusCode(),
                'body' => $e->getMessage(),
            ];
        }

        $response = $response->withHeader('Content-Type', 'text/html;charset=UTF-8');

        if (isset($result['body']) && $response->getBody()->isWritable()) {
            $response->getBody()->write($result['body']);
        }

        if (isset($result['headers'])) {
            foreach ($result['headers'] as $key => $value) {
                $response = $response->withAddedHeader($key, $value);
            }
        }

        return $response->withStatus($result['status_code'] ?? 200);
    }

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
     * @param string $user
     * @param string $error
     * @return string
     */
    public function getFormHtml(string $user = '', string $error = ''): string
    {
        return '<html>
<body>
<form method="POST">
    '.($error ? '<p>' . $error . '</p>' : '').'
    <label for="email">Email</label>
    <input id="email" type="email" name="user" value="'. $user .'" placeholder="Email address" required>
    <label for="password">Password</label>
    <input id="password" type="password" name="password" required>
    <input type="hidden" name="nonce" value="'. $this->nonce .'">
    <input type="submit" value="Authorize">
</form>
</body>
</html>';
    }
}
