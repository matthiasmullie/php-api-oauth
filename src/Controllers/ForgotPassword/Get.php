<?php

namespace MatthiasMullie\ApiOauth\Controllers\ForgotPassword;

use League\Route\Http\Exception;
use MatthiasMullie\ApiOauth\Controllers\Authorize\AuthorizeTrait as Authorize;
use MatthiasMullie\ApiOauth\Controllers\Authenticate\Post as Authenticate;
use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception\NotFoundException;
use MatthiasMullie\ApiOauth\Email\Message;

class Get extends Base
{
    /**
     * @var string
     */
    protected $resetPasswordHandler = 'MatthiasMullie\\ApiOauth\\Controllers\\ResetPassword\\Get';

    /**
     * @inheritdoc
     */
    protected function get(array $args, array $get): array
    {
        // validate user
        $user = $this->findUser(['email' => $get['email']]);
        if (count($user) === 0) {
            throw new NotFoundException('Not Found');
        }

        // find root application
        $application = $this->findApplication(['application' => $this->application]);
        if (count($application) === 0) {
            throw new Exception('No root application');
        }

        // create a session to reset the access token
        $accessToken = $this->createSession($application['client_id'], $user['user_id'], ['reset-password']);

        // build link to reset-password form
        $url = $this->getUrl($this->resetPasswordHandler, 'GET', ['user_id' => $user['user_id']]);
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query(['access_token' => $accessToken]);

        // prepare email
        $subject = trim($this->parse('reset-password-email-subject', ['email' => $user['email'], 'url' => $url]));
        $html = trim($this->parse('reset-password-email-html', ['email' => $user['email'], 'url' => $url]));
        $plain = trim($this->parse('reset-password-email-plain', ['email' => $user['email'], 'url' => $url]));
        $message = new Message($user['email'], $this->context['email']['from'], $subject);
        $message->setHtml($html);
        $message->setText($plain);

        try {
            $this->mailer->send($message);
        } catch (\Exception $e) {
            throw new Exception('Failed to send email');
        }

        return [];
    }

    /**
     * @param string $clientId
     * @param string $userId
     * @param array $scopes
     * @return string
     * @throws Exception
     */
    protected function createSession(string $clientId, string $userId, array $scopes): string
    {
        // generate code & refresh token
        $grantId = hash('sha1', $this->getRandom($clientId . $userId));
        $refreshToken = hash('sha1', $this->getRandom($grantId));

        // initiate session
        $this->database->beginTransaction();

        $statement = $this->database->prepare(
            'INSERT INTO grants (grant_id, client_id, user_id, refresh_token, expiration)
            VALUES (:grant_id, :client_id, :user_id, :refresh_token, :expiration)'
        );
        $statement->execute([
            ':grant_id' => $grantId,
            ':client_id' => $clientId,
            ':user_id' => $userId,
            ':refresh_token' => $refreshToken,
            ':expiration' => Authorize::$expiration,
        ]);

        $statement = $this->database->prepare(
            'INSERT INTO scopes (grant_id, scope)
            VALUES (:grant_id, :scope)'
        );
        foreach ($scopes as $scope) {
            $statement->execute([
                ':grant_id' => $grantId,
                ':scope' => $scope,
            ]);
        }

        // create the session
        $accessToken = hash('sha1', $this->getRandom($refreshToken));
        $statement = $this->database->prepare(
            'INSERT INTO sessions (grant_id, access_token, expiration)
            VALUES (:grant_id, :access_token, :expiration)'
        );
        $statement->execute([
            ':grant_id' => $grantId,
            ':access_token' => $accessToken,
            ':expiration' => time() + Authenticate::$expiration,
        ]);

        $status = $this->database->commit();
        if ($status === false) {
            throw new Exception('Unknown error');
        }

        return $accessToken;
    }
}
