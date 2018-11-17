<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

class AuthenticateTest extends BaseTestCase
{
    protected $newUserId;
    protected $code;

    public function setUp()
    {
        parent::setUp();

        // parent already installs everything and authenticates the new user
        // since we want to test authentication here, we're going to have to
        // create a new user and only authorize it
        $email = 'new-user@example.com';
        $password = 'my-password';

        // create a new user
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => $email,
                'password' => $password,
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $this->newUserId = $data['user_id'];

        // authorize new user for test application
        $response = $this->request(
            'POST',
            '/unsafe-authorize',
            [],
            [
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'email' => $email,
                'password' => $password,
                'scope' => 'private,testing'
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $this->code = $data['code'];
    }

    public function testBadMethod()
    {
        $response = $this->request('GET', '/authenticate');
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(405, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Method Not Allowed', $data['reason_phrase']);
    }

    public function testInvalidQueryParameter()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            ['i-dont-exist' => 'some-value'],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testMissingClientId()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: client_id', $data['reason_phrase']);
    }

    public function testMissingClientSecret()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'grant_type' => 'authorization_code',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: client_secret', $data['reason_phrase']);
    }

    public function testMissingGrantType()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: grant_type', $data['reason_phrase']);
    }

    public function testInvalidCode()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => '1234567890123456789012345678901234567890',
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: code, client_id or client_secret, or expired code', $data['reason_phrase']);
    }

    public function testInvalidClientId()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => '1234567890123456789012345678901234567890',
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: code, client_id or client_secret, or expired code', $data['reason_phrase']);
    }

    public function testInvalidClientSecret()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => '1234567890123456789012345678901234567890',
                'grant_type' => 'authorization_code',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: code, client_id or client_secret, or expired code', $data['reason_phrase']);
    }

    public function testInvalidGrantType()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => '1234567890123456789012345678901234567890',
                'grant_type' => 'an-invalid-grant-type',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: grant_type', $data['reason_phrase']);
    }

    public function testAuthenticate()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('issued_at', $data);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertEquals(['private', 'testing'], $data['scope']);
    }

    public function testDoubleAuthenticate()
    {
        // authenticate & fetch access token
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'];

        // verify that we can use access token
        $response = $this->request(
            'GET',
            '/users/'.$this->newUserId,
            ['access_token' => $accessToken]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('email', $data);

        // now try to re-authenticate, using the same code
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Forbidden re-use of authorization code', $data['reason_phrase']);

        // verify that we can no longer use access token
        $response = $this->request(
            'GET',
            '/users/'.$this->newUserId,
            ['access_token' => $accessToken]
        );
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid or expired)', $data['reason_phrase']);
    }

    public function testRefresh()
    {
        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'code' => $this->code,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'authorization_code',
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'];
        $refreshToken = $data['refresh_token'];

        $response = $this->request(
            'POST',
            '/authenticate',
            [],
            [
                'refresh_token' => $refreshToken,
                'client_id' => $this->testApplicationClientId,
                'client_secret' => $this->testApplicationClientSecret,
                'grant_type' => 'refresh_token',
            ]
        );

        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('issued_at', $data);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertEquals(['private', 'testing'], $data['scope']);
        $this->assertNotEquals($accessToken, $data['access_token']);
    }
}
