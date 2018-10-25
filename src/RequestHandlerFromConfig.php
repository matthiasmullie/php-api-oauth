<?php

namespace MatthiasMullie\ApiOauth;

use MatthiasMullie\Api\RequestHandler;
use MatthiasMullie\ApiOauth\Validators\ValidatorFactory;
use MatthiasMullie\PathConverter\Converter;
use Symfony\Component\Yaml\Yaml;

class RequestHandlerFromConfig extends RequestHandler
{
    /**
     * @param string $dir
     * @throws \MatthiasMullie\Api\Routes\Providers\Exception
     */
    public function __construct($dir = __DIR__.'/../config/')
    {
        $config = rtrim($dir, '/').'/config.yml';
        $routes = rtrim($dir, '/').'/routes.yml';

        $contents = file_get_contents($config);
        $data = Yaml::parse($contents);

        // rather than using realpath, I'll use converted, because the paths
        // defined in config.json are relative to that config file, not this file
        $converter = new Converter($dir, '/', '/');

        $routes = new YamlRouteProviderWithContext(
            $routes,
            array_merge($data, [
                'template_path' => '/'.ltrim($converter->convert($data['template_path']), '/'),
                'validators' => new ValidatorFactory(),
            ])
        );

        parent::__construct($routes);
    }
}
