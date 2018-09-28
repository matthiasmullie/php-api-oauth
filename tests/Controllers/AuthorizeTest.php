<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

class AuthorizeTest extends BaseTestCase
{
    public function testBadMethod()
    {
        $response = $this->request('PUT', '/authorize');
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(405, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Method Not Allowed', $data['reason_phrase']);
    }

    public function testInvalidQueryParameter()
    {
        $response = $this->request(
            'GET',
            '/authorize',
            ['client_id' => $this->testApplicationClientId, 'redirect_uri' => 'http://localhost', 'scope' => 'private', 'i-dont-exist' => 'some-value'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid: i-dont-exist', (string) $response->getBody());
    }

    public function testGetMissingClientId()
    {
        $response = $this->request(
            'GET',
            '/authorize',
            ['redirect_uri' => 'http://localhost', 'scope' => 'private'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetMissingRedirectUri()
    {
        $response = $this->request(
            'GET',
            '/authorize',
            ['client_id' => $this->testApplicationClientId, 'scope' => 'private'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetMissingScope()
    {
        $response = $this->request(
            'GET',
            '/authorize',
            ['client_id' => $this->testApplicationClientId, 'redirect_uri' => 'http://localhost'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetForm()
    {
        $response = $this->request(
            'GET',
            '/authorize',
            ['client_id' => $this->testApplicationClientId, 'redirect_uri' => 'http://localhost', 'scope' => 'private'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostMissingClientId()
    {
        $response = $this->request(
            'POST',
            '/authorize',
            ['redirect_uri' => 'http://localhost', 'scope' => 'private'],
            ['email' => $this->user, 'password' => $this->password, 'nonce' => 'an-invalid-nonce']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostMissingRedirectUri()
    {
        $response = $this->request(
            'POST',
            '/authorize',
            ['client_id' => $this->testApplicationClientId, 'scope' => 'private'],
            ['email' => $this->user, 'password' => $this->password, 'nonce' => 'an-invalid-nonce']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostMissingScope()
    {
        $response = $this->request(
            'POST',
            '/authorize',
            ['client_id' => $this->testApplicationClientId, 'redirect_uri' => 'http://localhost'],
            ['email' => $this->user, 'password' => $this->password, 'nonce' => 'an-invalid-nonce']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostMissingPassword()
    {
        $response = $this->request(
            'POST',
            '/authorize',
            ['redirect_uri' => 'http://localhost', 'client_id' => $this->testApplicationClientId],
            ['email' => $this->user]
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostMissingUser()
    {
        $response = $this->request(
            'POST',
            '/authorize',
            ['redirect_uri' => 'http://localhost', 'client_id' => $this->testApplicationClientId],
            ['password' => $this->password]
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostMissingNonce()
    {
        $response = $this->request(
            'POST',
            '/authorize',
            ['redirect_uri' => 'http://localhost', 'client_id' => $this->testApplicationClientId],
            ['email' => $this->user, 'password' => $this->password]
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPostInvalidNonce()
    {
        $response = $this->request(
            'POST',
            '/authorize',
            ['redirect_uri' => 'http://localhost', 'client_id' => $this->testApplicationClientId],
            ['email' => $this->user, 'password' => $this->password, 'nonce' => 'an-invalid-nonce']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
    }

    // can't do further testing - can't match nonce...
}
