<?php

namespace MatthiasMullie\ApiOauth\Controllers\ResetPassword;

class Get extends Base
{
    /**
     * @inheritdoc
     */
    protected function get(array $args, array $get): array
    {
        return ['body' => $this->parse('reset-password-form-html')];
    }
}
