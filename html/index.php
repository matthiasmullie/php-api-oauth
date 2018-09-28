<?php

use GuzzleHttp\Psr7\ServerRequest;
use MatthiasMullie\Api\RequestHandler;
use MatthiasMullie\ApiOauth\YamlRouteProviderWithContext;
use MatthiasMullie\ApiOauth\Validators\ValidatorFactory;
use Symfony\Component\Yaml\Yaml;

require __DIR__.'/../vendor/autoload.php';

$contents = file_get_contents(__DIR__.'/../config/config.yml');
$data = Yaml::parse($contents);
$database = new PDO(
    $data['database']['dsn'],
    $data['database']['username'],
    $data['database']['password'],
    $data['database']['options']
);

$routes = new YamlRouteProviderWithContext(
    __DIR__.'/../config/routes.yml',
    array_merge($data, ['database' => $database, 'validators' => new ValidatorFactory()])
);
$handler = new RequestHandler($routes);
$request = ServerRequest::fromGlobals();
// ServerRequest's parsedBody gets filled from $_POST, but that isn't set for PUT requests etc...
parse_str((string) $request->getBody(), $post);
$request = $request->withParsedBody($post);
$response = $handler->route($request);
$handler->output($response);
