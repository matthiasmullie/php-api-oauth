<?php

namespace MatthiasMullie\ApiOauth\Controllers\Application;

class Base extends \MatthiasMullie\ApiOauth\Controllers\Base
{
    /**
     * @inheritdoc
     */
    protected function getSessionRequirements(string $method, array $args, array $get, array $post): array
    {
        $rootApp = $this->findApplication(['application' => $this->application]);
        $requirements = ['client_id' => $rootApp['client_id']];

        if ($method !== 'POST' && isset($args['client_id'])) {
            $requestedApp = $this->findApplication(['client_id' => $args['client_id']]);
            $requirements['user_id'] = $requestedApp['user_id'];
        }

        return $requirements;
    }

    /**
     * @param string $userId
     * @return int
     */
    protected function getAmountOfApplicationsForUser(string $userId): int
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*)
            FROM applications
            WHERE user_id = :user_id'
        );
        $statement->execute([':user_id' => $userId]);
        $result = $statement->fetchColumn();
        return (int) $result;
    }
}
