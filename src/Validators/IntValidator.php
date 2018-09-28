<?php

namespace MatthiasMullie\ApiOauth\Validators;

class IntValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false) {
            throw new Exception('Not an int');
        }
        return $value;
    }
}
