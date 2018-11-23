<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

class LoginTest extends BaseTestCase
{
    public function testBadMethod()
    {
        $response = $this->request('PUT', '/login');
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(405, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Method Not Allowed', $data['reason_phrase']);
    }

    public function testInvalidQueryParameter()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
                'email' => $this->user,
                'password' => $this->password,
                'i-dont-exist' => 'some-value'
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testPostMissingClientId()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_secret' => $this->rootApplicationClientSecret,
                'email' => $this->user,
                'password' => $this->password,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: client_id', $data['reason_phrase']);
    }

    public function testPostMissingClientSecret()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'email' => $this->user,
                'password' => $this->password,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: client_secret', $data['reason_phrase']);
    }

    public function testPostMissingEmail()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
                'password' => $this->password,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: email', $data['reason_phrase']);
    }

    public function testPostMissingPassword()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
                'email' => $this->user,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: password', $data['reason_phrase']);
    }

    public function testPostInvalidClientId()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => '1234567890123456789012345678901234567890',
                'client_secret' => $this->rootApplicationClientSecret,
                'email' => $this->user,
                'password' => $this->password,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: client_id or client_secret', $data['reason_phrase']);
    }

    public function testPostInvalidClientSecret()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => '1234567890123456789012345678901234567890',
                'email' => $this->user,
                'password' => $this->password,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: client_id or client_secret', $data['reason_phrase']);
    }

    public function testPostInvalidEmail()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
                'email' => 'invalid-user@example.com',
                'password' => $this->password,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid email or password', $data['reason_phrase']);
    }

    public function testPostInvalidPassword()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
                'email' => $this->user,
                'password' => 'invalid-password',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid email or password', $data['reason_phrase']);
    }

    public function testPostLogin()
    {
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
                'email' => $this->user,
                'password' => $this->password,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('issued_at', $data);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertEquals(['root'], $data['scope']);
    }
}
