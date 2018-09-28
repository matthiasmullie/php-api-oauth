<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

use MatthiasMullie\ApiOauth\Tests\BaseRequestTestCase;

abstract class BaseTestCase extends BaseRequestTestCase
{
    protected $userId;
    protected $user = 'test-user@example.com';
    protected $password = 'test-password';
    protected $rootAccessToken;
    protected $testAccessToken;
    protected $rootApplication = 'root-app';
    protected $rootApplicationClientId;
    protected $rootApplicationClientSecret;
    protected $testApplication = 'test-app';
    protected $testApplicationClientId;
    protected $testApplicationClientSecret;

    public function setUp()
    {
        parent::setUp();

        $this->request('POST', '/reset');

        // set up clean database
        $response = $this->request('POST', '/install');
        $data = json_decode((string) $response->getBody(), true);
        $this->rootApplicationClientId = $data['client_id'];
        $this->rootApplicationClientSecret = $data['client_secret'];

        // create user
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => $this->user,
                'password' => $this->password,
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $this->userId = $data['user_id'];
        $this->rootAccessToken = $data['access_token'];

        // create application
        $response = $this->request(
            'POST',
            '/applications',
            ['access_token' => $this->rootAccessToken],
            ['application' => $this->testApplication]
        );
        $data = json_decode((string) $response->getBody(), true);
        $this->testApplicationClientId = $data['client_id'];
        $this->testApplicationClientSecret = $data['client_secret'];

        // authorize test user for test application
        $response = $this->request(
            'POST',
            '/unsafe-login',
            [],
            [
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'email' => $this->user,
                'password' => $this->password,
                'scope' => 'private'
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $code = $data['code'];

        // authenticate test user for test application
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $this->testAccessToken = $data['access_token'];
    }
}
