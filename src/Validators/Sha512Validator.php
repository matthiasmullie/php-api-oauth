<?php

namespace MatthiasMullie\ApiOauth\Validators;

class Sha512Validator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        return preg_match('/^[a-f0-9]{128}$/i', (string) $value) === 1;
    }

    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        if (!$this->validate($value)) {
            throw new Exception('Not sha512');
        }
        return strtolower((string) $value);
    }
}
