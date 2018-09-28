<?php

namespace MatthiasMullie\ApiOauth\Validators;

class NumberValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false) {
            throw new Exception('Not a number');
        }
        return $value;
    }
}
