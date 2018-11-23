<?php

namespace MatthiasMullie\ApiOauth\Controllers\Login;

use MatthiasMullie\ApiOauth\Controllers\Authenticate\AuthenticateTrait;
use MatthiasMullie\ApiOauth\Controllers\Authorize\AuthorizeTrait;
use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception\UnauthorizedException;

class Post extends Base
{
    use AuthorizeTrait;
    use AuthenticateTrait;

    /**
     * {@inheritdoc}
     */
    public function post(array $args, array $get, array $post): array
    {
        // validate application
        $application = $this->findApplication([
            'application' => $this->application,
            'client_id' => $post['client_id'],
            'client_secret' => $post['client_secret'],
        ]);
        if (count($application) === 0) {
            throw new UnauthorizedException('Invalid: client_id or client_secret');
        }

        // validate user
        $user = $this->findUser([
            'email' => $post['email'],
            'password' => hash('sha512', $post['password']),
        ]);
        if (count($user) === 0) {
            throw new UnauthorizedException('Invalid email or password');
        }

        $scopes = ['root'];
        $code = $this->authorize($application['client_id'], $user['user_id'], $scopes);
        $authentication = $this->authenticate($application['client_id'], $application['client_secret'], $code);

        // don't expose password
        unset($user['password']);

        return array_merge($user, $authentication);
    }
}
