<?php

namespace MatthiasMullie\ApiOauth\Controllers;

use League\Route\Http\Exception;
use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\NotFoundException;
use PDO;

/**
 * This is a generic controller that only requires a table & list of primary keys
 * to be configured, and it'll figure things out from there, as long as you stick
 * to some conventions.
 *
 * The idea is to use $args for data that references primary key data, use $_POST for
 * data that is to be stored in database, and use $_GET for other, non-data purposes
 * (e.g. auth with access code, sorting data sets, ...)
 *
 * Keys in $args and $_POST must map directly to their database counterparts.
 * The access_token, if needed, must be present in $_GET.
 * If you're exposing data that needs scope/session validation, you must remember to
 * define getSessionRequirements as well.
 */
abstract class Generic extends Base
{
    /**
     * @return string
     */
    abstract protected function getTable(): string;

    /**
     * @return array
     */
    abstract protected function getPrimaryKey(): array;

    /**
     * @inheritdoc
     */
    protected function get(array $args, array $get): array
    {
        return $this->fetch($args);
    }

    /**
     * @inheritdoc
     */
    protected function post(array $args, array $get, array $post): array
    {
        $data = array_merge($args, $post);

        $table = $this->getTable();

        $columns = [];
        $values = [];
        $params = [];

        foreach ($data as $column => $value) {
            $columns[] = $column;
            $values[] = ":{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);
        }

        $statement = $this->database->prepare(
            'INSERT INTO '. $table .'('. implode(', ', $columns) .')
            VALUES ('. implode(', ', $values) .')'
        );
        $status = $statement->execute($params);

        if ($status === false || $statement->rowCount() === 0) {
            throw new Exception(500, 'Unknown error');
        }

        // fetch instead of just returning $data, since there could be more
        // columns (with default data) in DB
        return $this->fetch($data);
    }

    /**
     * @inheritdoc
     */
    protected function put(array $args, array $get, array $post): array
    {
        $data = array_merge($args, $post);

        $table = $this->getTable();

        $columns = [];
        $values = [];
        $params = [];

        foreach ($data as $column => $value) {
            $columns[] = $column;
            $values[] = ":{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);
        }

        $statement = $this->database->prepare(
            'REPLACE INTO '. $table .'('. implode(', ', $columns) .')
            VALUES ('. implode(', ', $values) .')'
        );
        $status = $statement->execute($params);

        if ($status === false) {
            throw new Exception(500, 'Unknown error');
        }

        // fetch instead of just returning $data, since there could be more
        // columns (with default data) in DB
        return $this->fetch($data);
    }

    /**
     * @inheritdoc
     */
    protected function patch(array $args, array $get, array $post): array
    {
        $data = array_merge($args, $post);

        $table = $this->getTable();
        $pk = $this->getPrimaryKey();

        // split up data: $pks will be all primary keys (only used for WHERE),
        // $data will then contain the rest
        $pks = array_intersect_key($data, array_fill_keys($pk, null));

        // we didn't get data for all primary keys...
        if (count($pks) !== count($pk)) {
            throw new Exception(500, 'Internal error: missing primary key(s)');
        }

        // fetch existing data & see how it stacks up
        $existing = $this->fetch($args);
        $scopes = $this->getScopes('PATCH', $args, $get, $post);
        $data = $this->sanitize($data, $this->methods['PATCH']['form_params'] ?? [], $scopes);
        if (empty($data) || $this->sanitize($existing, $this->methods['PATCH']['form_params'] ?? [], $scopes) === $post) {
            // if there are no changes to the data, we don't even have to execute
            // an update statement...
            return array_merge($existing, $data);
        }

        $set = [];
        $where = [];
        $params = [];

        foreach ($data as $column => $value) {
            $set[] = "$column = :{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);
        }

        foreach ($pks as $column => $value) {
            $where[] = "$column = :{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);
        }

        $statement = $this->database->prepare(
            'UPDATE '. $table .'
            SET '. implode(', ', $set) .'
            WHERE '. implode(' AND ', $where)
        );

        $status = $statement->execute($params);

        if ($status === false || $statement->rowCount() === 0) {
            throw new Exception(500, 'Unknown error');
        }

        return array_merge($existing, $data);
    }

    /**
     * @inheritdoc
     */
    protected function delete(array $args, array $get, array $post): array
    {
        // first check if we can fetch this record... (a 404 will be thrown
        // if we can't find it...)
        $this->fetch($args);

        $data = $args;

        $table = $this->getTable();

        $where = [];
        $params = [];

        foreach ($data as $column => $value) {
            $where[] = "$column = :{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);
        }

        $statement = $this->database->prepare(
            'DELETE FROM '. $table .'
            WHERE '. implode(' AND ', $where)
        );
        $status = $statement->execute($params);

        if ($status === false || $statement->rowCount() === 0) {
            throw new ForbiddenException('Unknown error');
        }

        return [];
    }

    /**
     * @param array $conditions
     * @return array
     * @throws Exception
     */
    protected function fetch(array $conditions): array
    {
        $table = $this->getTable();

        $where = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $where[] = "$column = :{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);
        }

        $statement = $this->database->prepare(
            'SELECT *
            FROM '. $table .'
            WHERE '. implode(' AND ', $where)
        );
        $statement->execute($params);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        if ($result === false) {
            throw new Exception(500, 'Unknown error');
        }

        if (empty($result)) {
            throw new NotFoundException('Not Found');
        }

        $result = array_map(function ($value) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : $value;
        }, $result[0]);

        return $result;
    }
}
