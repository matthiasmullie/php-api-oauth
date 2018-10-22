<?php

namespace MatthiasMullie\ApiOauth\Controllers\ResetPassword;

use MatthiasMullie\ApiOauth\Controllers\HtmlBase;

/**
 * This is *not* an API endpoint, but an HTML form.
 * Password reset *could* be done via API (provided the access token
 * is valid) but also needs an HTML version, because users will click
 * a link they've been emailed...
 */
class Base extends HtmlBase
{
    /**
     * @inheritdoc
     */
    protected function getSessionRequirements(string $method, array $args, array $get, array $post): array
    {
        $rootApp = $this->findApplication(['application' => $this->application]);
        return [
            'client_id' => $rootApp['client_id'],
            'user_id' => $args['user_id'],
        ];
    }
}
