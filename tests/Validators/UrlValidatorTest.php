<?php

namespace MatthiasMullie\ApiOauth\Tests\Validators;

use MatthiasMullie\ApiOauth\Validators\UrlValidator;
use MatthiasMullie\ApiOauth\Validators\ValidatorInterface;

class UrlValidatorTest extends ValidatorTestCase
{
    /**
     * @inheritdoc
     */
    public function getValidator(): ValidatorInterface
    {
        return new UrlValidator();
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
            [['a' => 'b'], false, null],
            [1, false, null],
            [0, false, null],
            [true, false, null],
            ['1', false, null],
            ['off', false, null],
            ['', false, null],
            ['test@example.com', false, null],
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
            ['http://www.mullie.eu', true, 'http://www.mullie.eu'],
            ['http://www.mullie.eu:80', true, 'http://www.mullie.eu:80'],
            ['http://www.mullie.eu:80?code=123', true, 'http://www.mullie.eu:80?code=123'],
            ['http://www.mullie.eu:80?code=123&redirect_uri=', true, 'http://www.mullie.eu:80?code=123&redirect_uri='],
        ];
    }
}
