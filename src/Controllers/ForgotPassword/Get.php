<?php

namespace MatthiasMullie\ApiOauth\Controllers\ForgotPassword;

use League\Route\Http\Exception;
use MatthiasMullie\ApiOauth\Controllers\Authenticate\AuthenticateTrait;
use MatthiasMullie\ApiOauth\Controllers\Authorize\AuthorizeTrait;
use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception\NotFoundException;
use MatthiasMullie\ApiOauth\Email\Message;

class Get extends Base
{
    use AuthorizeTrait;
    use AuthenticateTrait;

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
            throw new Exception(500, 'Internal error: no root application');
        }

        // create a session to reset the access token
        $code = $this->authorize($application['client_id'], $user['user_id'], ['reset-password']);
        $authentication = $this->authenticate($application['client_id'], $application['client_secret'], $code);

        // build link to reset-password form
        $url = $this->getUrl($this->resetPasswordHandler, 'GET', ['user_id' => $user['user_id']]);
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query(['access_token' => $authentication['access_token']]);

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
            throw new Exception(500, 'Internal error: failed to send email');
        }

        return [];
    }
}
