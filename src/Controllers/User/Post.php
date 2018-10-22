<?php

namespace MatthiasMullie\ApiOauth\Controllers\User;

use MatthiasMullie\ApiOauth\Controllers\Base;
use MatthiasMullie\ApiOauth\Controllers\Authorize\AuthorizeTrait as Authorize;
use MatthiasMullie\ApiOauth\Controllers\Authenticate\Post as Authenticate;
use League\Route\Http\Exception;
use League\Route\Http\Exception\BadRequestException;

class Post extends Base
{
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

        $this->database->beginTransaction();

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

        // bypass authorization and immediately store & return access token as well
        $grantId = hash('sha1', $this->getRandom($post['client_id'] . $data['user_id']));
        $refreshToken = hash('sha1', $this->getRandom($grantId));
        $accessToken = hash('sha1', $this->getRandom($refreshToken));
        $expiration = time() + Authenticate::$expiration;


        // start a session for the main application
        $statement = $this->database->prepare(
            'INSERT INTO grants (grant_id, client_id, user_id, refresh_token, expiration)
            VALUES (:grant_id, :client_id, :user_id, :refresh_token, :expiration)'
        );
        $statement->execute([
            ':grant_id' => $grantId,
            ':client_id' => $post['client_id'],
            ':user_id' => $data['user_id'],
            ':refresh_token' => $refreshToken,
            ':expiration' => time() + Authorize::$expiration,
        ]);

        $statement = $this->database->prepare(
            'INSERT INTO scopes (grant_id, scope)
            VALUES (:grant_id, :scope)'
        );
        $statement->execute([
            ':grant_id' => $grantId,
            ':scope' => 'root',
        ]);

        $statement = $this->database->prepare(
            'INSERT INTO sessions (grant_id, access_token, expiration)
            VALUES (:grant_id, :access_token, :expiration)'
        );
        $statement->execute([
            ':grant_id' => $grantId,
            ':access_token' => $accessToken,
            ':expiration' => $expiration,
        ]);

        $result = $this->database->commit();
        if ($result === false) {
            throw new Exception('Unknown error');
        }

        // don't expose password
        unset($data['password']);

        return array_merge($data, [
            'access_token' => $accessToken,
            'issued_at' => time(),
            'expires_in' => $expiration - time(),
            'refresh_token' => $refreshToken,
            'scope' => ['root'],
        ]);
    }
}
