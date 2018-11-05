<?php

namespace MatthiasMullie\ApiOauth\Controllers\Application;

use League\Route\Http\Exception;
use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\NotFoundException;
use PDO;

class Delete extends Base
{
    /**
     * @inheritdoc
     */
    protected function delete(array $args, array $get, array $post): array
    {
        $application = $this->findApplication(['client_id' => $args['client_id']]);
        if (count($application) === 0) {
            throw new NotFoundException('Not Found');
        }

        $this->database->beginTransaction();

        $statement = $this->database->prepare(
            'DELETE FROM applications
            WHERE client_id = :client_id'
        );
        $statement->execute([':client_id' => $application['client_id']]);

        $statement = $this->database->prepare(
            'SELECT grant_id
            FROM grants
            WHERE client_id = :client_id'
        );
        $statement->execute([':client_id' => $application['client_id']]);
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
            throw new Exception(500, 'Unknown error');
        }

        return [];
    }
}
