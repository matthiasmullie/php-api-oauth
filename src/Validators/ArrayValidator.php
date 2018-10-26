<?php

namespace MatthiasMullie\ApiOauth\Validators;

/**
 * `Array` is an array with just values: non-associative.
 * Like this, in JSON: ['a', 'b', 'c']
 */
class ArrayValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        return is_array($value) && (count($value) === 0 || array_keys($value) === range(0, count($value) - 1));
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
