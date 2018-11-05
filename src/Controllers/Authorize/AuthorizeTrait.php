<?php

namespace MatthiasMullie\ApiOauth\Controllers\Authorize;

use League\Route\Http\Exception;
use PDO;

trait AuthorizeTrait
{
    /**
     * @var int
     */
    public static $expiration = 10 * 60; // valid for 10 minutes

    /**
     * @param string $clientId
     * @param string $userId
     * @param array $scopes
     * @return string
     * @throws Exception
     */
    protected function authorize(string $clientId, string $userId, array $scopes): string
    {
        // remove expired, unused grants, scopes & sessions while we're here...
        $statement = $this->database->prepare(
            'SELECT grants.grant_id
            FROM grants
            LEFT OUTER JOIN sessions ON sessions.grant_id = grants.grant_id AND sessions.expiration >= :now
            WHERE grants.expiration < :now AND sessions.grant_id IS NULL'
        );
        $statement->execute([':now' => time()]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $grantIds = array_column($result, 'grant_id');

        $statement = $this->database->prepare(
            'DELETE FROM grants
            WHERE grant_id IN (:grant_ids)'
        );
        $statement->execute([':grant_ids' => $grantIds]);

        $statement = $this->database->prepare(
            'DELETE FROM scopes
            WHERE grant_id IN (:grant_ids)'
        );
        $statement->execute([':grant_ids' => $grantIds]);

        $statement = $this->database->prepare(
            'DELETE FROM sessions
            WHERE grant_id IN (:grant_ids)'
        );
        $statement->execute([':grant_ids' => $grantIds]);

        // generate code & refresh token
        $grantId = hash('sha1', $this->getRandom($clientId . $userId));
        $refreshToken = hash('sha1', $this->getRandom($grantId));

        // initiate session
        $this->database->beginTransaction();

        $statement = $this->database->prepare(
            'INSERT INTO grants (grant_id, client_id, user_id, refresh_token, expiration)
            VALUES (:grant_id, :client_id, :user_id, :refresh_token, :expiration)'
        );
        $statement->execute([
            ':grant_id' => $grantId,
            ':client_id' => $clientId,
            ':user_id' => $userId,
            ':refresh_token' => $refreshToken,
            ':expiration' => time() + static::$expiration,
        ]);

        $statement = $this->database->prepare(
            'INSERT INTO scopes (grant_id, scope)
            VALUES (:grant_id, :scope)'
        );
        foreach ($scopes as $scope) {
            $statement->execute([
                ':grant_id' => $grantId,
                ':scope' => $scope,
            ]);
        }

        $status = $this->database->commit();
        if ($status === false) {
            throw new Exception(500, 'Unknown error');
        }

        return $grantId;
    }
}
