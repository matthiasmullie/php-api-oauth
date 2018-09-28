<?php

namespace MatthiasMullie\ApiOauth\Controllers\Reset;

use MatthiasMullie\ApiOauth\Controllers\Base;
use League\Route\Http\Exception;

/**
 * CAUTION!
 * This controller is NOT meant to be exposed, as it would let others
 * destroy the database. It is convenient for testing, though... :)
 */
class Post extends Base
{
    /**
     * {@inheritdoc}
     */
    public function post(array $args, array $get, array $post): array
    {
        $queries = [
            'DROP TABLE IF EXISTS applications',
            'DROP TABLE IF EXISTS users',
            'DROP TABLE IF EXISTS grants',
            'DROP TABLE IF EXISTS scopes',
            'DROP TABLE IF EXISTS sessions',
        ];

        $this->database->beginTransaction();
        foreach ($queries as $query) {
            $this->database->exec($query);
        }
        $status = $this->database->commit();
        if ($status === false) {
            throw new Exception('Unknown error');
        }

        return [];
    }
}
