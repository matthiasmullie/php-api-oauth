<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

class ForgotPasswordTest extends BaseTestCase
{
    public function testBadMethod()
    {
        $response = $this->request('PUT', '/forgot-password');
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(405, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Method Not Allowed', $data['reason_phrase']);
    }

    public function testInvalidQueryParameter()
    {
        $response = $this->request(
            'GET',
            '/forgot-password',
            ['email' => $this->user, 'i-dont-exist' => 'some-value'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testGetMissingEmail()
    {
        $response = $this->request(
            'GET',
            '/forgot-password',
            [],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: email', $data['reason_phrase']);
    }

    public function testGetInvalidEmail()
    {
        $response = $this->request(
            'GET',
            '/forgot-password',
            ['email' => 'an-invalid-email'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: email must be email', $data['reason_phrase']);
    }

    public function testGetNonExistingEmail()
    {
        $response = $this->request(
            'GET',
            '/forgot-password',
            ['email' => 'non-existing@example.com'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Not Found', $data['reason_phrase']);
    }

    public function testGet()
    {
        $response = $this->request(
            'GET',
            '/forgot-password',
            ['email' => $this->user],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
