<?php

namespace MatthiasMullie\ApiOauth\Controllers;

use League\Route\Http\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HtmlBase extends Base
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        try {
            $result = $this->invoke($request, $response, $args);
        } catch (Exception $e) {
            $result = [
                'status_code' => $e->getStatusCode(),
                'body' => $e->getMessage(),
            ];
        }

        $response = $response->withHeader('Content-Type', 'text/html;charset=UTF-8');

        if (isset($result['body']) && $response->getBody()->isWritable()) {
            $response->getBody()->write($result['body']);
        }

        if (isset($result['headers'])) {
            foreach ($result['headers'] as $key => $value) {
                $response = $response->withAddedHeader($key, $value);
            }
        }

        return $response->withStatus($result['status_code'] ?? 200);
    }
}
