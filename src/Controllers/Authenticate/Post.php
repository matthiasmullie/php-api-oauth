<?php

namespace MatthiasMullie\ApiOauth\Controllers\Authenticate;

use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\ForbiddenException;
use PDO;

class Post extends Base
{
    use AuthenticateTrait;

    /**
     * {@inheritdoc}
     */
    public function post(array $args, array $get, array $post): array
    {
        switch ($post['grant_type']) {
            case 'authorization_code':
                if (!isset($post['code'])) {
                    throw new BadRequestException('Missing: code');
                }

                return $this->authenticate($post['client_id'], $post['client_secret'], $post['code']);
            case 'refresh_token':
                if (!isset($post['refresh_token'])) {
                    throw new BadRequestException('Missing: refresh_token');
                }

                return $this->refresh($post['client_id'], $post['client_secret'], $post['refresh_token']);
            default:
                throw new BadRequestException('Invalid: grant_type');
        }
    }
}
