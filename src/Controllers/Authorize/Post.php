<?php

namespace MatthiasMullie\ApiOauth\Controllers\Authorize;

use League\Route\Http\Exception;
use League\Route\Http\Exception\BadRequestException;

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
class Post extends Base
{
    use AuthorizeTrait;

    /**
     * @inheritdoc
     */
    protected function post(array $args, array $get, array $post): array
    {
        // validate nonce
        if ($this->previousNonce === '' || $this->previousNonce !== $post['nonce']) {
            $html = $this->getFormHtml(htmlentities($post['email']), 'Invalid nonce');
            throw new BadRequestException($html);
        }

        // validate scopes
        $scopes = array_map('trim', explode(',', $get['scope']));
        $diff = array_diff($scopes, $this->scopes);
        if (count($diff) > 0) {
            throw new BadRequestException('Invalid scope(s): '.implode(',', $diff));
        }

        // validate user
        $user = $this->findUser([
            'email' => $post['email'],
            'password' => hash('sha512', $post['password']),
        ]);
        if (count($user) === 0) {
            $html = $this->getFormHtml(htmlentities($post['email']), 'Invalid user');
            throw new BadRequestException($html);
        }

        // validate application
        $application = $this->findApplication(['client_id' => $post['client_id']]);
        if (count($application) === 0) {
            throw new BadRequestException('Invalid: client_id');
        }

        try {
            $code = $this->authorize($application['client_id'], $user['user_id'], $scopes);
        } catch (Exception $e) {
            $html = $this->getFormHtml(htmlentities($post['email']), 'Something went wrong, please try again');
            throw new Exception($html);
        }

        return [
            'status_code' => 307,
            'headers' => [
                'Location', $get['redirect_uri'].'?code='.$code,
            ],
        ];
    }
}
