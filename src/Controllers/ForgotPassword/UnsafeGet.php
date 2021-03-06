<?php

namespace MatthiasMullie\ApiOauth\Controllers\ForgotPassword;

use League\Route\Http\Exception;
use League\Route\Http\Exception\NotFoundException;

/**
 * CAUTION!
 * This controller is NOT meant to be exposed, as it would allow others to
 * capture the access token & reset a user's password.
 * A workflow involving ForgotPassword\Get should be used instead - this one
 * only exists to allow tests to reset the password.
 */
class UnsafeGet extends Get
{
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

        return [
            'user_id' => $user['user_id'],
            'access_token' => $authentication['access_token'],
        ];
    }
}
