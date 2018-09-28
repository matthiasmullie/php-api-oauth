<?php

namespace MatthiasMullie\ApiOauth\Controllers\User;

use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception\NotFoundException;

class Get extends Base
{
    /**
     * @inheritdoc
     */
    protected function get(array $args, array $get): array
    {
        $user = $this->findUser(['user_id' => $args['user_id']]);
        if (count($user) === 0) {
            throw new NotFoundException('Not Found');
        }

        // don't expose password
        unset($user['password']);

        return $user;
    }
}
