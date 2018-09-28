<?php

namespace MatthiasMullie\ApiOauth\Validators;

interface ValidatorInterface
{
    /**
     * Validate that the value (which may be a string, which is what
     * $_GET and $_POST params come as) is of the correct type (or
     * could be cast to it)
     *
     * @param mixed $value
     * @return bool
     */
    public function validate($value): bool;

    /**
     * Cast the value (which may be a string, which is what $_GET and
     * $_POST params come as) to the correct type.
     *
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    public function cast($value);
}
