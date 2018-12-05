<?php

namespace MatthiasMullie\ApiOauth\Controllers\Application;

use League\Route\Http\Exception;
use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\BadRequestException;

class Put extends Base
{
    /**
     * @inheritdoc
     */
    protected function put(array $args, array $get, array $post): array
    {
        $application = $this->findApplication(['client_id' => $args['client_id']]);
        if (count($application) === 0) {
            throw new NotFoundException('Not Found');
        }

        if (isset($post['application'])) {
            $existing = $this->findApplication(['application' => $post['application']]);
            if (count($existing) > 0) {
                throw new BadRequestException('Application exists');
            }
        }

        if (isset($post['user_id'])) {
            $user = $this->findUser(['user_id' => $post['user_id']]);
            if (count($user) === 0) {
                throw new BadRequestException('Invalid user');
            }

            if ($this->getAmountOfApplicationsForUser($post['user_id']) > 20) {
                throw new BadRequestException('User has too many applications');
            }
        }

        $data = array_merge($args, $post);
        $data['client_secret'] = $application['client_secret'];

        $columns = [];
        $values = [];
        $params = [];
        foreach ($data as $column => $value) {
            $columns[] = $column;
            $values[] = ":{$column}";
            $params[":{$column}"] = !is_array($value) ? $value : json_encode($value);;
        }

        $statement = $this->database->prepare(
            'REPLACE INTO applications ('. implode(', ', $columns) .')
            VALUES ('. implode(', ', $values) .')'
        );

        $result = $statement->execute($params);
        if ($result === false) {
            throw new Exception(500, 'Unknown error');
        }

        // fetch instead of just returning $data, since there could be more
        // columns (with default data) in DB
        return $this->findApplication(['client_id' => $args['client_id']]);
    }
}
