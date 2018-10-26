<?php

namespace MatthiasMullie\ApiOauth\Tests\Validators;

use MatthiasMullie\ApiOauth\Validators\HexadecimalValidator;
use MatthiasMullie\ApiOauth\Validators\ValidatorInterface;

class HexadecimalValidatorTest extends ValidatorTestCase
{
    /**
     * @inheritdoc
     */
    public function getValidator(): ValidatorInterface
    {
        return new HexadecimalValidator();
    }

    /**
     * @inheritdoc
     */
    public function provider(): array
    {
        return [
            ['123', true, '123'],
            [123, true, '123'],
            ['abc', true, 'abc'],
            ['not valid', false, null],
            [['test'], false, null],
            [[1, 2, 3], false, null],
            [[['test']], false, null],
            [[], false, null],
            ['["a","b"]', false, null],
            ['{"a":"b"}', false, null],
            [['a' => 'b'], false, null],
            [1, true, '1'],
            [0, true, '0'],
            [true, true, '1'],
            ['1', true, '1'],
            ['off', false, null],
            ['', false, null],
            ['test@example.com', false, null],
            ['123.456', false, null],
            [123.456, false, null],
            ['00ff00', true, '00ff00'],
            ['01234567890abcdef01234567890abcdef01234', true, '01234567890abcdef01234567890abcdef01234'],
            ['01234567890abcdef01234567890abcdef012345', true, '01234567890abcdef01234567890abcdef012345'],
            ['01234567890abcdef01234567890abcdef0123456', true, '01234567890abcdef01234567890abcdef0123456'],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567', true, '01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567'],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef012345678', true, '01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef012345678'],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef0123456789', true, '01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef0123456789'],
            ['www.mullie.eu', false, null],
            ['http://www.mullie.eu', false, null],
            ['http://www.mullie.eu:80', false, null],
            ['http://www.mullie.eu:80?code=123', false, null],
            ['http://www.mullie.eu:80?code=123&redirect_uri=', false, null],
        ];
    }
}
