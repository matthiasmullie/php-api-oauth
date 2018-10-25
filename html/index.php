<?php

use GuzzleHttp\Psr7\ServerRequest;
use MatthiasMullie\ApiOauth\RequestHandlerFromConfig;

require __DIR__.'/../vendor/autoload.php';

$handler = new RequestHandlerFromConfig();
$request = ServerRequest::fromGlobals();
// ServerRequest's parsedBody gets filled from $_POST, but that isn't set for PUT requests etc...
parse_str((string) $request->getBody(), $post);
$request = $request->withParsedBody($post);
$response = $handler->route($request);
$handler->output($response);
