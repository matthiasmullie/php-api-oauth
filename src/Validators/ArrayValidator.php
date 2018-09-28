<?php

namespace MatthiasMullie\ApiOauth\Validators;

class ArrayValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        return is_array($value);
    }

    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        if (!$this->validate($value)) {
            throw new Exception('Not an array');
        }

        return (array) $value;
    }
}
