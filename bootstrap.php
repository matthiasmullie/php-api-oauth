<?php

use MatthiasMullie\Api\RequestHandler;
use MatthiasMullie\ApiOauth\Validators\ValidatorFactory;
use MatthiasMullie\ApiOauth\YamlRouteProviderWithContext;
use MatthiasMullie\PathConverter\Converter;
use Symfony\Component\Yaml\Yaml;

require __DIR__.'/vendor/autoload.php';

$contents = file_get_contents(__DIR__.'/config/config.yml');
$data = Yaml::parse($contents);

// rather than using realpath, I'll use converted, because the paths
// defined in config.json are relative to that config file, not this file
$converter = new Converter(__DIR__.'/config', '/', '/');

$routes = new YamlRouteProviderWithContext(
    __DIR__.'/config/routes.yml',
    array_merge($data, [
        'template_path' => '/'.ltrim($converter->convert($data['template_path']), '/'),
        'validators' => new ValidatorFactory(),
    ])
);
$handler = new RequestHandler($routes);
