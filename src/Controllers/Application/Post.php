<?php

namespace MatthiasMullie\ApiOauth\Controllers\Application;

use League\Route\Http\Exception;
use League\Route\Http\Exception\BadRequestException;

class Post extends Base
{
    /**
     * @inheritdoc
     */
    protected function post(array $args, array $get, array $post): array
    {
        $session = $this->getSession($get['access_token']);
        if ($this->getAmountOfApplicationsForUser($session['user_id']) > 20) {
            throw new BadRequestException('User has too many applications');
        }

        $application = $this->findApplication(['application' => $post['application']]);
        if (count($application) > 0) {
            throw new BadRequestException('Application exists');
        }

        // new application data
        $data = array_merge([
            'application' => $post['application'],
            'client_id' => hash('sha1', $this->getRandom($post['application'])),
            'client_secret' => hash('sha1', $this->getRandom('secret-' . $post['application'])),
            'user_id' => $session['user_id']
        ], $post);

        $columns = [];
        $values = [];
        $params = [];
        foreach ($data as $column => $value) {
            $columns[] = $column;
            $values[] = ":{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);
        }

        $statement = $this->database->prepare(
            'INSERT INTO applications ('. implode(', ', $columns) .')
            VALUES ('. implode(', ', $values) .')'
        );

        $result = $statement->execute($params);
        if ($result === false) {
            throw new Exception(500, 'Unknown error');
        }

        return $data;
    }
}
