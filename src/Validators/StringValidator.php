<?php

namespace MatthiasMullie\ApiOauth\Validators;

class StringValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        return method_exists($value, '__toString') || $value === null || is_scalar($value);
    }

    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        if (!$this->validate($value)) {
            throw new Exception('Not a string');
        }
        return (string) $value;
    }
}
