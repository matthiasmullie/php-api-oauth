<?php

namespace MatthiasMullie\ApiOauth\Validators;

class UrlValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        $value = filter_var($value, FILTER_VALIDATE_URL);
        if ($value === false) {
            throw new Exception('Not a url');
        }
        return $value;
    }
}
