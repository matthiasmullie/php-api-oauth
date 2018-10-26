<?php

namespace MatthiasMullie\ApiOauth\Validators;

/**
 * `Object` doesn't mean objects like in the traditional PHP sense,
 * but a key-value object literal (which, in PHP, is an associative
 * array)
 * Like this, in JSON: {'a': 'b', 'c': 'd'}
 */
class ObjectValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        $value = is_string($value) ? json_decode($value, true) : $value;
        return is_array($value) && (count($value) === 0 || array_keys($value) !== range(0, count($value) - 1));
    }

    /**
     * @inheritdoc
     */
    public function cast($value)
    {
        if (!$this->validate($value)) {
            throw new Exception('Not an object');
        }

        return is_string($value) ? json_decode($value, true) : $value;
    }
}
