<?php

namespace MatthiasMullie\ApiOauth\Controllers\User;

use MatthiasMullie\ApiOauth\Controllers\Authenticate\AuthenticateTrait;
use MatthiasMullie\ApiOauth\Controllers\Authorize\AuthorizeTrait;
use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception\BadRequestException;

class Post extends Base
{
    use AuthorizeTrait;
    use AuthenticateTrait;

    /**
     * @inheritdoc
     */
    protected function post(array $args, array $get, array $post): array
    {
        // validate root application client_id & client_secret
        $application = $this->findApplication([
            'application' => $this->application,
            'client_id' => $post['client_id'],
            'client_secret' => $post['client_secret'],
        ]);
        if (count($application) === 0) {
            throw new BadRequestException('Invalid: client_id or client_secret');
        }

        $user = $this->findUser(['email' => $post['email']]);
        if (count($user) > 0) {
            throw new BadRequestException('Email exists');
        }

        $data = [
            'user_id' => hash('sha1', $this->getRandom($post['email'])),
            'email' => $post['email'],
            'password' => hash('sha512', $post['password']),
        ];

        $columns = [];
        $values = [];
        $params = [];
        foreach ($data as $column => $value) {
            $columns[] = $column;
            $values[] = ":{$column}";
            $params[":{$column}"] = $value;
        }

        // insert user
        $statement = $this->database->prepare(
            'INSERT INTO users ('. implode(', ', $columns) .')
            VALUES ('. implode(', ', $values) .')'
        );
        $statement->execute($params);

        // immediately authorize & authenticate root session
        $scopes = ['root'];
        $code = $this->authorize($post['client_id'], $data['user_id'], $scopes);
        $authentication = $this->authenticate($post['client_id'], $post['client_secret'], $code);

        // don't expose password
        unset($data['password']);

        return array_merge($data, $authentication);
    }
}
