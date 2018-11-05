<?php

namespace MatthiasMullie\ApiOauth\Controllers\Install;

use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception;

class Post extends Base
{
    /**
     * {@inheritdoc}
     */
    public function post(array $args, array $get, array $post): array
    {
        $queries = [
            'CREATE TABLE applications (
                application VARCHAR(255) NOT NULL,
                client_id CHAR(40) NOT NULL PRIMARY KEY,
                client_secret CHAR(40) NOT NULL,
                user_id CHAR(40) DEFAULT NULL,
                UNIQUE (application),
                UNIQUE (client_secret)
            )',
            'CREATE TABLE users (
                user_id CHAR(40) NOT NULL PRIMARY KEY,
                email VARCHAR(254) NOT NULL,
                password CHAR(128) NOT NULL,
                UNIQUE (email)
            )',
            'CREATE TABLE grants (
                grant_id CHAR(40) NOT NULL PRIMARY KEY,
                client_id CHAR(40) NOT NULL,
                user_id CHAR(40) NOT NULL,
                refresh_token CHAR(40) NOT NULL,
                expiration INT(64) DEFAULT 0
            )',
            'CREATE TABLE scopes (
                grant_id CHAR(40) NOT NULL,
                scope VARCHAR(255) NOT NULL,
                UNIQUE (grant_id, scope)
            )',
            'CREATE TABLE sessions (
                grant_id CHAR(40) NOT NULL,
                access_token CHAR(40) DEFAULT NULL,
                expiration INT(64) DEFAULT 0
            )',
            'CREATE INDEX idx_applications_user_lookup ON applications (user_id)',
            'CREATE INDEX idx_users_email_lookup ON users (email)',
            'CREATE INDEX idx_grants_user_application_lookup ON grants (client_id, user_id)',
            'CREATE INDEX idx_grants_refresh_lookup ON grants (refresh_token)',
            'CREATE INDEX idx_session_access_lookup ON sessions (access_token)',
        ];

        $this->database->beginTransaction();

        // create tables
        foreach ($queries as $query) {
            $this->database->exec($query);
        }

        $clientId = hash('sha1', $this->getRandom($this->application));
        $clientSecret = hash('sha1', $this->getRandom('secret-' . $this->application));
        $data = [
            'application' => $this->application,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];

        // insert default application
        $statement = $this->database->prepare(
            'INSERT INTO applications (application, client_id, client_secret) 
            VALUES (:application, :client_id, :client_secret)'
        );
        $statement->execute([
            ':application' => $data['application'],
            ':client_id' => $data['client_id'],
            ':client_secret' => $data['client_secret'],
        ]);

        $status = $this->database->commit();
        if ($status === false) {
            throw new Exception(500, 'Unknown error');
        }

        return $data;
    }
}
