<?php

namespace MatthiasMullie\ApiOauth\Tests\Validators;

use MatthiasMullie\ApiOauth\Validators\AnyValidator;
use MatthiasMullie\ApiOauth\Validators\ValidatorInterface;

class AnyValidatorTest extends ValidatorTestCase
{
    /**
     * @inheritdoc
     */
    public function getValidator(): ValidatorInterface
    {
        return new AnyValidator();
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
            ['not valid', 'true', 'not valid'],
            [['test'], true, ['test']],
            [[1, 2, 3], true, [1, 2, 3]],
            [[['test']], true, [['test']]],
            [[], true, []],
            [['a' => 'b'], true, ['a' => 'b']],
            [1, true, '1'],
            [0, true, '0'],
            [true, true, '1'],
            ['1', true, '1'],
            ['off', true, 'off'],
            ['', true, ''],
            ['test@example.com', true, 'test@example.com'],
            ['123.456', true, '123.456'],
            [123.456, true, '123.456'],
            ['00ff00', true, '00ff00'],
            ['01234567890abcdef01234567890abcdef01234', true, '01234567890abcdef01234567890abcdef01234'],
            ['01234567890abcdef01234567890abcdef012345', true, '01234567890abcdef01234567890abcdef012345'],
            ['01234567890abcdef01234567890abcdef0123456', true, '01234567890abcdef01234567890abcdef0123456'],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567', true, '01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567'],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef012345678', true, '01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef012345678'],
            ['01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef0123456789', true, '01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef01234567890abcdef0123456789'],
            ['www.mullie.eu', true, 'www.mullie.eu'],
            ['http://www.mullie.eu', true, 'http://www.mullie.eu'],
            ['http://www.mullie.eu:80', true, 'http://www.mullie.eu:80'],
            ['http://www.mullie.eu:80?code=123', true, 'http://www.mullie.eu:80?code=123'],
            ['http://www.mullie.eu:80?code=123&redirect_uri=', true, 'http://www.mullie.eu:80?code=123&redirect_uri='],
        ];
    }
}
