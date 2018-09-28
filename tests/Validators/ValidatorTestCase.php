<?php

namespace MatthiasMullie\ApiOauth\Tests\Validators;

use MatthiasMullie\ApiOauth\Validators\Exception;
use MatthiasMullie\ApiOauth\Validators\ValidatorInterface;
use PHPUnit\Framework\TestCase;

abstract class ValidatorTestCase extends TestCase
{
    /**
     * @return ValidatorInterface
     */
    abstract public function getValidator(): ValidatorInterface;

    /**
     * @return array
     */
    abstract public function provider(): array;

    /**
     * @param mixed $value
     * @param bool $expect
     * @dataProvider provider
     */
    public function testValidator($value, bool $expect)
    {
        $this->assertEquals($expect, $this->getValidator()->validate($value));
    }

    /**
     * @param mixed $value
     * @param bool $valid
     * @param mixed $expect
     * @dataProvider provider
     * @throws Exception
     */
    public function testCast($value, bool $valid, $expect)
    {
        if ($valid) {
            $this->assertEquals($expect, $this->getValidator()->cast($value));
        } else {
            $this->expectException(Exception::class);
            $this->getValidator()->cast($value);
        }
    }
}
