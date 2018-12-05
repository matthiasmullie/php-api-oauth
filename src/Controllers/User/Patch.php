<?php

namespace MatthiasMullie\ApiOauth\Controllers\User;

use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\ForbiddenException;

class Patch extends Base
{
    /**
     * @inheritdoc
     */
    protected function patch(array $args, array $get, array $post): array
    {
        // user not found
        $user = $this->findUser(['user_id' => $args['user_id']]);
        if (count($user) === 0) {
            throw new NotFoundException('Not Found');
        }

        // check if new user doesn't already exist
        if (isset($post['email'])) {
            $newUser = $this->findUser(['email' => $post['email']]);
            if (count($newUser) > 0) {
                throw new BadRequestException('Email exists');
            }
        }

        // updating another user is not allowed
        $session = $this->getSession($get['access_token']);
        if ($user['user_id'] !== $session['user_id']) {
            throw new ForbiddenException('Cannot update other user');
        }

        // password is going to be hashed before storing
        if (isset($post['password'])) {
            $post['password'] = hash('sha512', $post['password']);
        }
        $data = array_merge($user, $post);

        // short-circuit if there are no changes
        if ($user === $data) {
            return $data;
        }

        $sql = [];
        $params = [];
        foreach ($data as $column => $value) {
            $sql[] = "$column = :{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);;
        }

        $statement = $this->database->prepare(
            'UPDATE users
            SET '. implode(', ', $sql) .'
            WHERE user_id = :user_id'
        );

        $result = $statement->execute($params);
        if ($result === false) {
            throw new Exception(500, 'Unknown error');
        }

        // don't expose password
        unset($data['password']);

        return $data;
    }
}
