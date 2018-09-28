<?php

namespace MatthiasMullie\ApiOauth\Validators;

class BoolValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw new Exception('Not a bool');
        }
        return $value;
    }
}
