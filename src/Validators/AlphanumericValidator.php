<?php

namespace MatthiasMullie\ApiOauth\Validators;

class AlphanumericValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        return (is_string($value) || is_numeric($value)) && preg_match('/^[a-z0-9]+$/i', (string) $value) === 1;
    }

    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        if (!$this->validate($value)) {
            throw new Exception('Not alphanumeric');
        }
        return (string) $value;
    }
}
