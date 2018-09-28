<?php

namespace MatthiasMullie\ApiOauth\Validators;

class ValidatorFactory
{
    /**
     * @var ValidatorInterface[]
     */
    protected $validators = [];

    /**
     * @param array $validators
     */
    public function __construct(array $validators = [])
    {
        $validators = array_merge([
            // add some default validators
            'any' => new AnyValidator(),
            'string' => new StringValidator(),
            'number' => new NumberValidator(),
            'int' => new IntValidator(),
            'float' => new FloatValidator(),
            'bool' => new BoolValidator(),
            'array' => new ArrayValidator(),
            'alphanumeric' => new AlphanumericValidator(),
            'hexadecimal' => new HexadecimalValidator(),
            'email' => new EmailValidator(),
            'sha1' => new Sha1Validator(),
            'sha512' => new Sha512Validator(),
            'url' => new UrlValidator(),
        ], $validators);

        foreach ($validators as $type => $validator) {
            $this->registerValidator($type, $validator);
        }
    }

    /**
     * @param string $type
     * @param ValidatorInterface $validator
     */
    public function registerValidator(string $type, ValidatorInterface $validator)
    {
        $this->validators[$type] = $validator;
    }

    /**
     * @param string $type
     * @return ValidatorInterface
     * @throws Exception
     */
    public function getValidator(string $type): ValidatorInterface
    {
        if (!isset($this->validators[$type])) {
            throw new Exception('Unknown validator: '.$type);
        }

        return $this->validators[$type];
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return bool
     * @throws Exception
     */
    public function validate(string $type, $value): bool
    {
        return $this->getValidator($type)->validate($value);
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    public function cast(string $type, $value)
    {
        return $this->getValidator($type)->cast($value);
    }
}
