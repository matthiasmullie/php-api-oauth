<?php

namespace MatthiasMullie\ApiOauth\Validators;

class AnyValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        return $value;
    }
}
