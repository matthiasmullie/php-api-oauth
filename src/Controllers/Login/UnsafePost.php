<?php

namespace MatthiasMullie\ApiOauth\Controllers\Login;

use MatthiasMullie\ApiOauth\Controllers\Authorize\AuthorizeTrait;
use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\UnauthorizedException;

/**
 * CAUTION!
 * This controller is NOT meant to be exposed, as it would allow applications
 * to capture the user password while they're performing the authentication.
 * A workflow involving Login\Post should be used instead - this one
 * only exists to allow tests to login.
 */
class UnsafePost extends Base
{
    use AuthorizeTrait;

    /**
     * {@inheritdoc}
     */
    public function post(array $args, array $get, array $post): array
    {
        // validate application
        $application = $this->findApplication([
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

        // validate scopes
        $scopes = array_map('trim', explode(',', $post['scope']));
        $diff = array_diff($scopes, $this->scopes);
        if (count($diff) > 0) {
            throw new BadRequestException('Invalid scope(s): '.implode(',', $diff));
        }

        $code = $this->authorize($application['client_id'], $user['user_id'], $scopes);

        return [
            'code' => $code,
        ];
    }
}
