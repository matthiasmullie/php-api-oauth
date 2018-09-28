<?php

namespace MatthiasMullie\ApiOauth\Controllers\Authorize;

use League\Route\Http\Exception\BadRequestException;

/**
 * This is *not* an API endpoint, but an HTML form.
 * Authorization is not meant to be possible from an API endpoint,
 * because that would expose the actual password to the caller.
 * Instead, users who want to authorize an app access to their
 * data should be sent to this form that will allow them to log
 * in and then redirect to an application-specific location where
 * the application can obtain a token that can be exchanged for
 * an access token.
 */
class Get extends Base
{
    /**
     * @inheritdoc
     */
    protected function get(array $args, array $get): array
    {
        // validate scopes
        $scopes = array_map('trim', explode(',', $get['scope']));
        $diff = array_diff($scopes, $this->scopes);
        if (count($diff) > 0) {
            throw new BadRequestException('Invalid scope(s): '.implode(',', $diff));
        }

        $html = $this->getFormHtml();
        return ['body' => $html];
    }
}
