<?php

namespace MatthiasMullie\ApiOauth\Tests\Validators;

use MatthiasMullie\ApiOauth\Validators\EmailValidator;
use MatthiasMullie\ApiOauth\Validators\ValidatorInterface;

class EmailValidatorTest extends ValidatorTestCase
{
    /**
     * @inheritdoc
     */
    public function getValidator(): ValidatorInterface
    {
        return new EmailValidator();
    }

    /**
     * @inheritdoc
     */
    public function provider(): array
    {
        return [
            ['123', false, null],
            [123, false, null],
            ['abc', false, null],
            ['not valid', false, null],
            [['test'], false, null],
            [[1, 2, 3], false, null],
            [[['test']], false, null],
            [[], false, null],
            [1, false, null],
            [0, false, null],
            [true, false, null],
            ['1', false, null],
            ['off', false, null],
            ['', false, null],
            ['test@example.com', true, 'test@example.com'],
            ['123.456', false, null],
            [123.456, false, null],
            ['00ff00', false, null],
            ['01234567890abcdef01234567890abcdef01234', false, null],
            ['01234567890abcdef01234567890abcdef012345', false, null],
            ['01234567890abcdef01234567890abcdef0123456', false, null],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567', false, null],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef012345678', false, null],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef0123456789', false, null],
            ['www.mullie.eu', false, null],
            ['http://www.mullie.eu', false, null],
            ['http://www.mullie.eu:80', false, null],
            ['http://www.mullie.eu:80?code=123', false, null],
            ['http://www.mullie.eu:80?code=123&redirect_uri=', false, null],
        ];
    }
}
