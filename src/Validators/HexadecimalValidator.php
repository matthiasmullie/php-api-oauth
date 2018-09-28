<?php

namespace MatthiasMullie\ApiOauth\Validators;

class HexadecimalValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        return preg_match('/^[a-f0-9]+$/i', (string) $value) === 1;
    }

    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        if (!$this->validate($value)) {
            throw new Exception('Not hexadecimal');
        }
        return (string) $value;
    }
}
