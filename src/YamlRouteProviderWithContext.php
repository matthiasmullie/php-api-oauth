<?php

namespace MatthiasMullie\ApiOauth;

use MatthiasMullie\Api\Routes\Providers\Exception;
use MatthiasMullie\Api\Routes\Providers\YamlRouteProvider;

class YamlRouteProviderWithContext extends YamlRouteProvider
{
    /**
     * @var array
     */
    protected $context;

    /**
     * @param string $path Path to route data
     * @param array $context
     *
     * @throws Exception
     */
    public function __construct(string $path, array $context)
    {
        parent::__construct($path);
        $this->context = $context;
    }

    /**
     * @param array $data
     *
     * @return string[]
     *
     * @throws Exception
     */
    protected function getMethods(array $data): array
    {
        if (!isset($data['methods'])) {
            $serialized = serialize($data);
            throw new Exception("Missing methods. (input: $serialized)");
        }

        return array_keys($data['methods']);
    }

    /**
     * @inheritdoc
     */
    protected function getHandler(array $data): callable
    {
        if (
            isset($data['handler'], $data['methods']) &&
            is_string($data['handler']) &&
            is_array($data['methods']) &&
            method_exists($data['handler'], '__invoke')
        ) {
            return new $data['handler']($this->context, $data['methods']);
        }

        return parent::getHandler($data);
    }
}
