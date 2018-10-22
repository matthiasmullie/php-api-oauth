<?php

namespace MatthiasMullie\ApiOauth\Controllers\ResetPassword;

use League\Route\Http\Exception;

class Post extends Base
{
    /**
     * @inheritdoc
     */
    protected function post(array $args, array $get, array $post): array
    {
        $session = $this->getSession($get['access_token']);

        $this->database->beginTransaction();

        $statement = $this->database->prepare(
            'UPDATE users
            SET password = :password
            WHERE user_id = :user_id'
        );
        $statement->execute([
            ':password' => hash('sha512', $post['password']),
            ':user_id' => $args['user_id'],
        ]);

        // delete session, scopes & grant
        $statement = $this->database->prepare(
            'DELETE FROM sessions
            WHERE grant_id = :grant_id'
        );
        $statement->execute(['grant_id' => $session['grant_id']]);

        $statement = $this->database->prepare(
            'DELETE FROM scopes
            WHERE grant_id = :grant_id'
        );
        $statement->execute(['grant_id' => $session['grant_id']]);

        $statement = $this->database->prepare(
            'DELETE FROM grants
            WHERE grant_id = :grant_id'
        );
        $statement->execute(['grant_id' => $session['grant_id']]);

        $status = $this->database->commit();
        if ($status === false) {
            throw new Exception('Unknown error');
        }

        return ['body' => $this->parse('reset-password-confirmation-html')];
    }
}
