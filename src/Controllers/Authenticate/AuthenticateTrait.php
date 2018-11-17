<?php

namespace MatthiasMullie\ApiOauth\Controllers\Authenticate;

use League\Route\Http\Exception;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\ForbiddenException;
use PDO;

trait AuthenticateTrait
{
    /**
     * @var int
     */
    public static $authenticateExpiration = 12 * 60 * 60; // valid for 12 hours

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $code
     * @return array
     * @throws Exception
     */
    protected function authenticate(string $clientId, string $clientSecret, string $code): array
    {
        // remove expired sessions while we're here...
        $statement = $this->database->prepare(
            'DELETE FROM sessions
            WHERE expiration < :now'
        );
        $statement->execute([':now' => time()]);

        // see if we can find the grant
        $statement = $this->database->prepare(
            'SELECT grant_id, refresh_token
            FROM grants
            WHERE
                grant_id = :grant_id AND
                expiration > :now AND
                client_id = (
                    SELECT client_id
                    FROM applications
                    WHERE
                        client_id = :client_id AND
                        client_secret = :client_secret
                )'
        );
        $statement->execute([
            ':grant_id' => $code,
            ':now' => time(),
            ':client_id' => $clientId,
            ':client_secret' => $clientSecret,
        ]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            throw new BadRequestException('Invalid: code, client_id or client_secret, or expired code');
        }
        $grantId = $result['grant_id'];
        $refreshToken = $result['refresh_token'];

        // make sure there are no existing sessions for this code
        $statement = $this->database->prepare(
            'DELETE FROM sessions
            WHERE grant_id = :grant_id'
        );
        $statement->execute([':grant_id' => $grantId]);
        if ($statement->rowCount() > 0) {
            throw new ForbiddenException('Forbidden re-use of authorization code');
        }

        // create the session
        $accessToken = hash('sha1', $this->getRandom($refreshToken));
        $statement = $this->database->prepare(
            'INSERT INTO sessions (grant_id, access_token, expiration)
            VALUES (:grant_id, :access_token, :expiration)'
        );
        $status = $statement->execute([
            ':grant_id' => $grantId,
            ':access_token' => $accessToken,
            ':expiration' => time() + static::$authenticateExpiration,
        ]);

        if ($status === false) {
            throw new Exception(500, 'Unknown error');
        }

        // figure out what scopes we've authenticated
        $session = $this->getSession($accessToken);

        return [
            'access_token' => $accessToken,
            'issued_at' => time(),
            'expires_in' => $session['expires_in'],
            'refresh_token' => $refreshToken,
            'scope' => $session['scopes'],
        ];
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $refreshToken
     * @return array
     * @throws Exception
     */
    protected function refresh(string $clientId, string $clientSecret, string $refreshToken): array
    {
        // remove expired sessions while we're here...
        $statement = $this->database->prepare(
            'DELETE FROM sessions
            WHERE expiration < :now'
        );
        $statement->execute([':now' => time()]);

        // see if we can find the grant
        $statement = $this->database->prepare(
            'SELECT grant_id, refresh_token
            FROM grants
            WHERE
                refresh_token = :refresh_token AND
                client_id = (
                    SELECT client_id
                    FROM applications
                    WHERE
                        client_id = :client_id AND
                        client_secret = :client_secret
                )'
        );
        $statement->execute([
            ':refresh_token' => $refreshToken,
            ':client_id' => $clientId,
            ':client_secret' => $clientSecret,
        ]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            throw new BadRequestException('Invalid: refresh_token, client_id or client_secret');
        }
        $grantId = $result['grant_id'];

        // create new session
        $accessToken = hash('sha1', $this->getRandom($grantId));
        $statement = $this->database->prepare(
            'INSERT INTO sessions (grant_id, access_token, expiration)
            VALUES (:grant_id, :access_token, :expiration)'
        );
        $status = $statement->execute([
            ':grant_id' => $grantId,
            ':access_token' => $accessToken,
            ':expiration' => time() + static::$authenticateExpiration,
        ]);

        if ($status === false) {
            throw new Exception(500, 'Unknown error');
        }

        // figure out what scopes we've authenticated
        $session = $this->getSession($accessToken);

        return [
            'access_token' => $accessToken,
            'issued_at' => time(),
            'expires_in' => $session['expires_in'],
            'scope' => $session['scopes'],
        ];
    }
}
