<?php

namespace MatthiasMullie\ApiOauth\Tests;

use MatthiasMullie\ApiOauth\Validators\ValidatorFactory;
use MatthiasMullie\ApiOauth\YamlRouteProviderWithContext;
use GuzzleHttp\Psr7\ServerRequest;
use MatthiasMullie\Api\RequestHandler;
use MatthiasMullie\Api\Routes\Providers\RouteProviderInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
     *
     * @throws \MatthiasMullie\Api\Routes\Providers\Exception
     */
    public function internalRequest($method, $uri, array $get = [], array $post = [])
    {
        $contents = file_get_contents(__DIR__.'/config/config.yml');
        $data = Yaml::parse($contents);

        $database = new PDO(
            $data['database']['dsn'],
            $data['database']['username'],
            $data['database']['password'],
            $data['database']['options']
        );

        $routes = new YamlRouteProviderWithContext(
            __DIR__.'/config/routes.yml',
            array_merge($data, ['database' => $database, 'validators' => new ValidatorFactory()])
        );

        $handler = new RequestHandler($routes);

        $request = new ServerRequest($method, $uri);
        $request = $request
            ->withQueryParams($get)
            ->withParsedBody($post);

        $response = $handler->route($request);

        // terminate DB connection
        $database = null;

        return $response;
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
            'base_uri' => 'http://server',
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
