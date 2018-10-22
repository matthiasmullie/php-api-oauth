<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

class ResetPasswordTest extends BaseTestCase
{
    /**
     * @var string
     */
    protected $resetUserId;

    /**
     * @var string
     */
    protected $resetAccessToken;

    public function setUp()
    {
        parent::setUp();

        $response = $this->request(
            'GET',
            '/unsafe-forgot-password',
            ['email' => $this->user],
            []
        );
        $data = json_decode((string) $response->getBody(), true);
        $this->resetUserId = $data['user_id'];
        $this->resetAccessToken = $data['access_token'];
    }

    public function testBadMethod()
    {
        $response = $this->request('PUT', '/reset-password/'. $this->resetUserId);
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(405, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Method Not Allowed', $data['reason_phrase']);
    }

    public function testInvalidArg()
    {
        $response = $this->request(
            'GET',
            '/reset-password/0123456789012345678901234567890123456789',
            ['access_token' => $this->resetAccessToken,],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Invalid: access_token (invalid user session)', (string) $response->getBody());
    }

    public function testInvalidQueryParameter()
    {
        $response = $this->request(
            'GET',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => $this->resetAccessToken, 'i-dont-exist' => 'some-value'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid: i-dont-exist', (string) $response->getBody());
    }

    public function testGetMissingAccessToken()
    {
        $response = $this->request(
            'GET',
            '/reset-password/'. $this->resetUserId,
            [],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Missing: access_token', (string) $response->getBody());
    }

    public function testGetInvalidAccessToken()
    {
        $response = $this->request(
            'GET',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => '0123456789012345678901234567890123456789'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Invalid: access_token (invalid or expired)', (string) $response->getBody());
    }

    public function testGetForm()
    {
        $response = $this->request(
            'GET',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => $this->resetAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostMissingAccessToken()
    {
        $response = $this->request(
            'POST',
            '/reset-password/'. $this->resetUserId,
            [],
            ['password' => 'new-password']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Missing: access_token', (string) $response->getBody());
    }

    public function testPostInvalidAccessToken()
    {
        $response = $this->request(
            'POST',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => '0123456789012345678901234567890123456789'],
            ['password' => 'new-password']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Invalid: access_token (invalid or expired)', (string) $response->getBody());
    }

    public function testPostMissingPassword()
    {
        $response = $this->request(
            'POST',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => $this->resetAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Missing: password', (string) $response->getBody());
    }

    public function testPostNewPassword()
    {
        $response = $this->request(
            'POST',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => $this->resetAccessToken],
            ['password' => 'new-password']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());

        // validate that we can now login with the new password
        $response = $this->request(
            'POST',
            '/login',
            [],
            [
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
                'email' => $this->user,
                'password' => 'new-password',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('code', $data);
    }

    public function testPostNewPasswordSameTokenTwice()
    {
        $response = $this->request(
            'POST',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => $this->resetAccessToken],
            ['password' => 'new-password']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->request(
            'POST',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => $this->resetAccessToken],
            ['password' => 'another-new-password']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Invalid: access_token (invalid or expired)', (string) $response->getBody());
    }

    public function testPostNewPasswordTwoResets()
    {
        // setUp has already generated a reset session - this should generate
        // a second one, both of which should work
        $response = $this->request(
            'GET',
            '/unsafe-forgot-password',
            ['email' => $this->user],
            []
        );
        $data = json_decode((string) $response->getBody(), true);
        $newResetUserId = $data['user_id'];
        $newResetAccessToken = $data['access_token'];

        // check if new reset session works
        $response = $this->request(
            'POST',
            '/reset-password/'. $newResetUserId,
            ['access_token' => $newResetAccessToken],
            ['password' => 'new-password']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());

        // check if first reset session works
        $response = $this->request(
            'POST',
            '/reset-password/'. $this->resetUserId,
            ['access_token' => $this->resetAccessToken],
            ['password' => 'new-password']
        );
        $this->assertArraySubset(['Content-Type' => ['text/html;charset=UTF-8']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
