<?php

namespace MatthiasMullie\ApiOauth\Controllers;

use Http\Adapter\Guzzle6\Client as HttpClient;
use League\Route\Http\Exception;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\MethodNotAllowedException;
use MatthiasMullie\Api\Controllers\JsonController;
use MatthiasMullie\Api\Routes\Providers\RouteProviderInterface;
use MatthiasMullie\ApiOauth\Validators\Exception as ValidatorException;
use MatthiasMullie\ApiOauth\Validators\ValidatorFactory;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stampie\Mailer;

abstract class Base extends JsonController
{
    /**
     * @var array
     */
    protected $context;

    /**
     * @var string[]
     */
    protected $scopes;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $templatePath;

    /**
     * @var PDO
     */
    protected $database;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var ValidatorFactory
     */
    protected $validators;

    /**
     * @var string
     */
    protected $application;

    /**
     * @var array
     */
    protected $methods;

    /**
     * @var RouteProviderInterface
     */
    protected $router;

    /**
     * @var array
     */
    protected $sessions;

    /**
     * @param RouteProviderInterface $router
     * @param array $context
     * @param array $methods
     */
    public function __construct(RouteProviderInterface $router, array $context, array $methods)
    {
        $this->router = $router;
        $this->context = $context;
        $this->scopes = $context['scopes'];
        $this->uri = $context['uri'];
        $this->templatePath = $context['template_path'];
        $this->validators = $context['validators'];
        $this->application = $context['application'];
        $this->methods = $methods;

        $this->database = new PDO(
            $context['database']['dsn'],
            $context['database']['username'],
            $context['database']['password'],
            $context['database']['options']
        );

        $this->mailer = new $context['email']['mailer']['class'](
            new HttpClient(),
            ...$context['email']['mailer']['args']
        );
    }

    /**
     * @inheritdoc
     */
    public function invoke(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $method = $request->getMethod();
        $get = $request->getQueryParams();
        $post = $request->getParsedBody();

        $scopes = $this->getScopes($method, $args, $get, $post);

        // validate & sanitize GET & POST params
        $this->validate($get, $this->methods[$method]['query'] ?? [], $scopes);
        $get = $this->sanitize($get, $this->methods[$method]['query'] ?? [], $scopes);
        $this->validate($post, $this->methods[$method]['form_params'] ?? [], $scopes);
        $post = $this->sanitize($post, $this->methods[$method]['form_params'] ?? [], $scopes);

        switch ($method) {
            case 'GET':
                $result = $this->get($args, $get);
                break;
            case 'POST':
                $result = $this->post($args, $get, $post);
                break;
            case 'PUT':
                $result = $this->put($args, $get, $post);
                break;
            case 'PATCH':
                $result = $this->patch($args, $get, $post);
                break;
            case 'DELETE':
                $result = $this->delete($args, $get, $post);
                break;
            case 'HEAD':
                $result = $this->head($args, $get);
                break;
            case 'OPTIONS':
                $result = $this->options($args, $get);
                break;
            default:
                throw new MethodNotAllowedException('Method Not Allowed');
        }

        // validate & sanitize outgoing data
        $this->validate($result, $this->methods[$method]['responses'] ?? [], $scopes);
        $result = $this->sanitize($result, $this->methods[$method]['responses'] ?? [], $scopes);

        return $result;
    }

    /**
     * @param array $args
     * @param array $get
     * @return array
     * @throws Exception
     */
    protected function get(array $args, array $get): array
    {
        // this method needs to be explicitly implemented in a child class
        $allowed = array_keys($this->methods);
        throw new MethodNotAllowedException($allowed, 'Method Not Allowed');
    }

    /**
     * @param array $args
     * @param array $get
     * @param array $post
     * @return array
     * @throws Exception
     */
    protected function post(array $args, array $get, array $post): array
    {
        // this method needs to be explicitly implemented in a child class
        $allowed = array_keys($this->methods);
        throw new MethodNotAllowedException($allowed, 'Method Not Allowed');
    }

    /**
     * @param array $args
     * @param array $get
     * @param array $post
     * @return array
     * @throws Exception
     */
    protected function put(array $args, array $get, array $post): array
    {
        // this method needs to be explicitly implemented in a child class
        $allowed = array_keys($this->methods);
        throw new MethodNotAllowedException($allowed, 'Method Not Allowed');
    }

    /**
     * @param array $args
     * @param array $get
     * @param array $post
     * @return array
     * @throws Exception
     */
    protected function patch(array $args, array $get, array $post): array
    {
        // this method needs to be explicitly implemented in a child class
        $allowed = array_keys($this->methods);
        throw new MethodNotAllowedException($allowed, 'Method Not Allowed');
    }

    /**
     * @param array $args
     * @param array $get
     * @param array $post
     * @return array
     * @throws Exception
     */
    protected function delete(array $args, array $get, array $post): array
    {
        // this method needs to be explicitly implemented in a child class
        $allowed = array_keys($this->methods);
        throw new MethodNotAllowedException($allowed, 'Method Not Allowed');
    }

    /**
     * @param array $args
     * @param array $get
     * @return array
     * @throws Exception
     */
    protected function head(array $args, array $get): array
    {
        // this method needs to be explicitly implemented in a child class
        $allowed = array_keys($this->methods);
        throw new MethodNotAllowedException($allowed, 'Method Not Allowed');
    }

    /**
     * @param array $args
     * @param array $get
     * @return array
     * @throws Exception
     */
    protected function options(array $args, array $get): array
    {
        // this method needs to be explicitly implemented in a child class
        $allowed = array_keys($this->methods);
        throw new MethodNotAllowedException($allowed, 'Method Not Allowed');
    }

    /**
     * @param string $method
     * @param array $args
     * @param array $get
     * @param array $post
     * @return bool
     */
    protected function hasAccessToken(string $method, array $args, array $get, array $post): bool
    {
        return isset($get['access_token']);
    }

    /**
     * @param string $method
     * @param array $args
     * @param array $get
     * @param array $post
     * @return string
     */
    protected function getAccessToken(string $method, array $args, array $get, array $post): string
    {
        return $get['access_token'];
    }

    /**
     * @param string $method
     * @param array $args
     * @param array $get
     * @param array $post
     * @return array
     */
    protected function getSessionRequirements(string $method, array $args, array $get, array $post): array
    {
        return array_filter([
            'user_id' => $method !== 'POST' && isset($args['user_id']) ? $args['user_id'] : null,
            'client_id' => $args['client_id'] ?? null,
        ]);
    }

    /**
     * @param array $data
     * @param array $validation
     * @param array $scopes
     * @throws Exception
     */
    protected function validate(array $data, array $validation, array $scopes)
    {
        $this->validateMissing($data, $validation);
        $this->validateRedundant($data, $validation);
        $this->validateType($data, $validation);
        $this->validateScope($data, $validation, $scopes);
    }

    /**
     * @param array $data
     * @param array $validation
     * @param array $scopes
     * @return array
     * @throws Exception
     */
    protected function sanitize(array $data, array $validation, array $scopes): array
    {
        $data = $this->removeOutOfScope($data, $validation, $scopes);
        $data = $this->castType($data, $validation);
        $data = $this->sort($data, $validation);

        return $data;
    }

    /**
     * @param array $data
     * @param array $validation
     * @throws BadRequestException
     */
    protected function validateMissing(array $data, array $validation)
    {
        $required = array_map(function ($data) {
            return $data['required'] ?? false;
        }, $validation);

        // $diff will contain all the missing keys
        $diff = array_diff_key($required, $data);
        // $filter will contain all the non-falsies - i.e. the ones required
        $filter = array_filter($diff);

        if (count($filter) > 0) {
            throw new BadRequestException('Missing: '.implode(', ', array_keys($filter)));
        }
    }

    /**
     * @param array $data
     * @param array $validation
     * @throws BadRequestException
     */
    protected function validateRedundant(array $data, array $validation)
    {
        // $diff will contain all the redundant keys
        $diff = array_diff_key($data, $validation);

        if (count($diff) > 0) {
            throw new BadRequestException('Invalid: '.implode(', ', array_keys($diff)));
        }
    }

    /**
     * @param array $data
     * @param array $validation
     * @throws BadRequestException
     * @throws Exception
     */
    protected function validateType(array $data, array $validation)
    {
        $types = array_map(function ($data) {
            return $data['type'] ?? 'any';
        }, $validation);

        foreach ($data as $key => $value) {
            $type = $types[$key];
            try {
                $validator = $this->validators->getValidator($type);
                if (!$validator->validate($value)) {
                    throw new BadRequestException('Invalid: '.$key.' must be '.$type);
                }
            } catch (ValidatorException $e) {
                throw new Exception('Internal error: '.$e->getMessage());
            }
        }
    }

    /**
     * @param array $data
     * @param array $validation
     * @param array $scopes
     * @throws ForbiddenException
     */
    protected function validateScope(array $data, array $validation, array $scopes)
    {
        $required = array_map(function ($data) {
            return $data['required'] ?? false;
        }, $validation);

        $requiredScopes = array_map(function ($data) {
            return $data['scope'] ?? ['public'];
        }, $validation);

        foreach ($data as $key => $value) {
            if (count(array_intersect($scopes, $requiredScopes[$key])) === 0 && $required[$key] ?? false) {
                throw new ForbiddenException(
                    'Missing access (required scope: '. implode(',', $requiredScopes[$key]).') for: '.$key
                );
            }
        }
    }

    /**
     * @param array $data
     * @param array $validation
     * @param array $scopes
     * @return array
     */
    protected function removeOutOfScope(array $data, array $validation, array $scopes): array
    {
        $requiredScopes = array_map(function ($data) {
            return $data['scope'] ?? ['public'];
        }, $validation);

        foreach ($data as $key => $value) {
            if (count(array_intersect($scopes, $requiredScopes[$key])) === 0) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $validation
     * @return array
     * @throws Exception
     */
    protected function castType(array $data, array $validation): array
    {
        $types = array_map(function ($data) {
            return $data['type'] ?? 'any';
        }, $validation);

        foreach ($data as $key => $value) {
            $type = $types[$key];
            try {
                $validator = $this->validators->getValidator($type);
                $data[$key] = $validator->cast($value);
            } catch (ValidatorException $e) {
                throw new Exception('Internal error: '.$e->getMessage());
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $validation
     * @return array
     */
    protected function sort(array $data, array $validation): array
    {
        return array_merge(array_intersect_key($validation, $data), $data);
    }

    /**
     * @param string $salt
     * @return string
     */
    protected function getRandom(string $salt): string
    {
        return uniqid($salt, true);
    }

    /**
     * @param string $accessToken
     * @return array
     */
    protected function getSession(string $accessToken): array
    {
        if (isset($this->sessions[$accessToken])) {
            return $this->sessions[$accessToken];
        }

        $statement = $this->database->prepare(
            'SELECT grants.grant_id, grants.client_id, grants.user_id, sessions.expiration, scopes.scope
            FROM sessions
            INNER JOIN grants ON grants.grant_id = sessions.grant_id
            INNER JOIN scopes ON scopes.grant_id = sessions.grant_id
            INNER JOIN users ON users.user_id = grants.user_id
            INNER JOIN applications ON applications.client_id = grants.client_id
            WHERE sessions.access_token = :access_token'
        );
        $statement->execute([':access_token' => $accessToken]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->sessions[$accessToken] = $result ? [
            'grant_id' => $result[0]['grant_id'],
            'client_id' => $result[0]['client_id'],
            'user_id' => $result[0]['user_id'],
            'expires_in' => max(0, $result[0]['expiration'] - time()),
            'scopes' => array_column($result, 'scope'),
        ] : [];

        return $this->sessions[$accessToken];
    }

    /**
     * @param string $method
     * @param array $args
     * @param array $get
     * @param array $post
     * @return array
     * @throws Exception
     */
    protected function getScopes(string $method, array $args, array $get, array $post): array
    {
        $default = ['public'];

        if (!$this->hasAccessToken($method, $args, $get, $post)) {
            return $default;
        }
        $accessToken = $this->getAccessToken($method, $args, $get, $post);

        $session = $this->getSession($accessToken);
        if (count($session) === 0 || $session['expires_in'] <= 0) {
            throw new ForbiddenException('Invalid: access_token (invalid or expired)');
        }

        // validate if the session for this access_token matches what is required to
        // access the requested data
        $requirements = $this->getSessionRequirements($method, $args, $get, $post);
        if (isset($requirements['user_id']) && $session['user_id'] !== $requirements['user_id']) {
            throw new ForbiddenException('Invalid: access_token (invalid user session)');
        }
        if (isset($requirements['client_id']) && $session['client_id'] !== $requirements['client_id']) {
            throw new ForbiddenException('Invalid: access_token (invalid application session)');
        }

        return array_merge($default, $session['scopes']);
    }

    /**
     * @param array $conditions
     * @return array
     */
    protected function findApplication(array $conditions): array
    {
        $sql = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $sql[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }

        $statement = $this->database->prepare(
            'SELECT *
            FROM applications
            WHERE ' . implode(' AND ', $sql)
        );
        $statement->execute($params);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    /**
     * @param array $conditions
     * @return array
     */
    protected function findUser(array $conditions): array
    {
        $sql = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $sql[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }

        $statement = $this->database->prepare(
            'SELECT *
            FROM users
            WHERE ' . implode(' AND ', $sql)
        );
        $statement->execute($params);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    /**
     * @param string $handler
     * @param string $method
     * @param array $args
     * @return string
     * @throws Exception
     */
    protected function getUrl(string $handler, string $method, array $args = []): string
    {
        foreach ($this->router->getRoutes() as $route) {
            if (!in_array($method, $route->getMethods())) {
                continue;
            }

            if (get_class($route->getHandler()) !== $handler) {
                continue;
            }

            $path = $route->getPath();

            // parse named args into the path
            $path = preg_replace_callback('/\{([^\}]+)\}/', function ($match) use ($args) {
                return $args[$match[1]] ?? $match[0];
            }, $path);

            return $this->uri . $path;
        }

        throw new Exception('Could not generate url for '. $method .' '. $handler);
    }

    /**
     * @param string $template
     * @param array $args
     * @return string
     */
    protected function parse(string $template, array $args = []): string
    {
        extract($args, EXTR_SKIP);
        ob_start();
        require rtrim($this->templatePath, '/') .'/'. $template .'.php';
        return ob_get_clean();
    }
}
