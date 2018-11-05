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

        $data = array_merge($application, $post);

        // short-circuit if there are no changes
        if ($application === $data) {
            return $data;
        }

        $sql = [];
        $params = [];
        foreach ($data as $column => $value) {
            $sql[] = "$column = :{$column}";
            $params[":{$column}"] = $value;
        }

        $statement = $this->database->prepare(
            'UPDATE applications
            SET '. implode(', ', $sql) .'
            WHERE client_id = :client_id'
        );

        $result = $statement->execute($params);
        if ($result === false) {
            throw new Exception(500, 'Unknown error');
        }

        return $data;
    }
}
