<?php

namespace MatthiasMullie\ApiOauth\Validators;

abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate($value): bool
    {
        try {
            $this->cast($value);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
