<?php

namespace MatthiasMullie\ApiOauth\Validators;

class EmailValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        $value = filter_var($value, FILTER_VALIDATE_EMAIL);
        if ($value === false) {
            throw new Exception('Not an email');
        }
        return $value;
    }
}
