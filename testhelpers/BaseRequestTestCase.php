<?php

namespace MatthiasMullie\ApiOauth\TestHelpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\ServerRequest;
use MatthiasMullie\ApiOauth\RequestHandlerFromConfig;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

abstract class BaseRequestTestCase extends TestCase
{
    /**
     * @param string $method
     * @param string $uri
     * @param array  $get
     * @param array  $post
     *
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function request($method, $uri, array $get = [], array $post = [])
    {
        $request = getEnv('REQUEST') ?: 'default';
        switch ($request) {
            case 'default':
                return $this->internalRequest($method, $uri, $get, $post);
            case 'http':
                return $this->httpRequest($method, $uri, $get, $post);
            default:
                throw new \Exception('Invalid request type: ' . $request);
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $get
     * @param array  $post
     *
     * @return ResponseInterface
     */
    public function internalRequest($method, $uri, array $get = [], array $post = [])
    {
        global $handler;

        // multiple requests would cause "Cannot register two routes matching ...",
        // so we need to re-init the routes for every request
        require __DIR__.'/../bootstrap.php';

        $request = new ServerRequest($method, $uri);
        $request = $request
            ->withQueryParams($get)
            ->withParsedBody($post);

        return $handler->route($request);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $get
     * @param array  $post
     *
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpRequest($method, $uri, array $get = [], array $post = [])
    {
        $client = new Client([
            'base_uri' => 'http://testserver',
        ]);

        $options = [
            'query' => $get,
            'form_params' => $post,
        ];

        try {
            return $client->request($method, $uri, $options);
        } catch (RequestException $e) {
            return $e->getResponse();
        }
    }
}
