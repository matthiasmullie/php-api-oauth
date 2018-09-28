<?php

namespace MatthiasMullie\ApiOauth\Controllers\User;

use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\ForbiddenException;
use PDO;

class Delete extends Base
{
    /**
     * @inheritdoc
     */
    protected function delete(array $args, array $get, array $post): array
    {
        // user not found
        $user = $this->findUser(['user_id' => $args['user_id']]);
        if (count($user) === 0) {
            throw new NotFoundException('Not Found');
        }

        // updating another user is not allowed
        $session = $this->getSession($get['access_token']);
        if ($user['user_id'] !== $session['user_id']) {
            throw new ForbiddenException('Cannot delete other user');
        }

        $this->database->beginTransaction();

        $statement = $this->database->prepare(
            'DELETE FROM users
            WHERE user_id = :user_id'
        );
        $statement->execute([':user_id' => $user['user_id']]);

        $statement = $this->database->prepare(
            'DELETE FROM applications
            WHERE user_id = :user_id'
        );
        $statement->execute([':user_id' => $user['user_id']]);

        $statement = $this->database->prepare(
            'SELECT grant_id
            FROM grants
            WHERE user_id < :user_id'
        );
        $statement->execute([':user_id' => $user['user_id']]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $grantIds = array_column($result, 'grant_id');
        if (count($grantIds) > 0) {
            $statement = $this->database->prepare(
                'DELETE FROM grants
                WHERE grant_id IN(:grant_ids)'
            );
            $statement->execute([':grant_ids' => $grantIds]);

            $statement = $this->database->prepare(
                'DELETE FROM scopes
                WHERE grant_id IN(:grant_ids)'
            );
            $statement->execute([':grant_ids' => $grantIds]);

            $statement = $this->database->prepare(
                'DELETE FROM sessions
                WHERE grant_id IN(:grant_ids)'
            );
            $statement->execute([':grant_ids' => $grantIds]);
        }

        $status = $this->database->commit();
        if ($status === false) {
            throw new Exception('Unknown error');
        }

        return [];
    }
}
