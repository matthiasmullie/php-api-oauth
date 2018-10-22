<?php

namespace MatthiasMullie\ApiOauth\Controllers\Application;

use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\NotFoundException;

class Get extends Base
{
    /**
     * @inheritdoc
     */
    protected function get(array $args, array $get): array
    {
        $application = $this->findApplication(['client_id' => $args['client_id']]);
        if (count($application) === 0) {
            throw new NotFoundException('Not Found');
        }

        return [
            'application' => $application['application'],
            'client_id' => $application['client_id'],
            'client_secret' => $application['client_secret'],
            'user_id' => $application['user_id'],
        ];
    }
}
