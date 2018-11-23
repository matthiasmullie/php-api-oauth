<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

class UserTest extends BaseTestCase
{
    public function testBadMethod()
    {
        $response = $this->request('OPTIONS', '/users/new-user@example.com');
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(405, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Method Not Allowed', $data['reason_phrase']);
    }

    public function testGetInvalidQueryParameter()
    {
        $response = $this->request(
            'GET',
            '/users/'.$this->userId,
            ['access_token' => $this->testAccessToken, 'i-dont-exist' => 'some-value']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testGetInvalidArg()
    {
        $response = $this->request(
            'GET',
            '/users/not-a-valid-user-id',
            ['access_token' => $this->testAccessToken]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testGetWithoutSession()
    {
        $response = $this->request(
            'GET',
            '/users/'.$this->userId
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function testGetWithInvalidSession()
    {
        $response = $this->request(
            'GET',
            '/users/'.$this->userId,
            ['access_token' => '1234567890123456789012345678901234567890']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid or expired)', $data['reason_phrase']);
    }

    public function testGetNonExistingUser()
    {
        $response = $this->request(
            'GET',
            '/users/1234567890123456789012345678901234567890',
            ['access_token' => $this->testAccessToken]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testGetOtherUser()
    {
        // create a new user that we'll have no business with
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $userId = $data['user_id'];

        $response = $this->request(
            'GET',
            '/users/'.$userId,
            ['access_token' => $this->testAccessToken]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testGetOtherUserNoSession()
    {
        $response = $this->request(
            'GET',
            '/users/'.$this->userId,
            [],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function testGetUser()
    {
        $response = $this->request(
            'GET',
            '/users/'.$this->userId,
            ['access_token' => $this->testAccessToken]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertEquals($data['email'], $this->user);
    }

    public function testPostInvalidQueryParameter()
    {
        $response = $this->request(
            'POST',
            '/users',
            ['i-dont-exist' => 'some-value'],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testPostInvalidEmail()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'not-an-email-address',
                'password' => hash('sha512', 'my-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: email must be email', $data['reason_phrase']);
    }

    public function testPostNewExistingUser()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => $this->user,
                'password' => hash('sha512', 'my-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Email exists', $data['reason_phrase']);
    }

    public function testPostNewUserMissingUser()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'password' => hash('sha512', 'my-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: email', $data['reason_phrase']);
    }

    public function testPostNewUserMissingPassword()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: password', $data['reason_phrase']);
    }

    public function testPostNewUserMissingClientId()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-password'),
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: client_id', $data['reason_phrase']);
    }

    public function testPostNewUserMissingClientSecret()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-password'),
                'client_id' => $this->rootApplicationClientId,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: client_secret', $data['reason_phrase']);
    }

    public function testPostNewUserInvalidClientId()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-password'),
                'client_id' => '1234567890123456789012345678901234567890',
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: client_id or client_secret', $data['reason_phrase']);
    }

    public function testPostNewUserInvalidClientSecret()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => '1234567890123456789012345678901234567890',
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: client_id or client_secret', $data['reason_phrase']);
    }

    public function testPostNewUser()
    {
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('user_id', $data);
    }

    public function testPutInvalidQueryParameter()
    {
        $response = $this->request(
            'PUT',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken, 'i-dont-exist' => 'some-value'],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password')
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testPutInvalidArg()
    {
        $response = $this->request(
            'PUT',
            '/users/not-a-valid-user-id',
            ['access_token' => $this->rootAccessToken],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testPutWithInvalidSession()
    {
        $response = $this->request(
            'PUT',
            '/users/'.$this->userId,
            ['access_token' => '1234567890123456789012345678901234567890'],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid or expired)', $data['reason_phrase']);
    }

    public function testPutUpdateUserId()
    {
        $response = $this->request(
            'PUT',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken],
            [
                'user_id' => 'cant-change-the-user-id',
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password')
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: user_id', $data['reason_phrase']);
    }

    public function testPutUpdateDuplicateUser()
    {
        // first account creation - should succeed
        $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );

        $response = $this->request(
            'PUT',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Email exists', $data['reason_phrase']);
    }

    public function testPutUpdateOtherUser()
    {
        // first account creation - should succeed
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'];

        $response = $this->request(
            'PUT',
            '/users/'.$this->userId,
            ['access_token' => $accessToken],
            [
                'email' => 'non-existing-user@example.com',
                'password' => hash('sha512', 'my-new-password')
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testPutUpdateUser()
    {
        $response = $this->request(
            'PUT',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
            ]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals($data['email'], 'new-user@example.com');
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('access_token', $data);
    }

    public function testPatchInvalidQueryParameter()
    {
        $response = $this->request(
            'PATCH',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken, 'i-dont-exist' => 'some-value'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testPatchInvalidArg()
    {
        $response = $this->request(
            'PATCH',
            '/users/not-a-valid-user-id',
            ['access_token' => $this->rootAccessToken],
            ['password' => hash('sha512', 'my-password')]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testPatchWithInvalidSession()
    {
        $response = $this->request(
            'PATCH',
            '/users/'.$this->userId,
            ['access_token' => '1234567890123456789012345678901234567890'],
            ['password' => hash('sha512', 'my-password')]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid or expired)', $data['reason_phrase']);
    }

    public function testPatchUpdateUserId()
    {
        $response = $this->request(
            'PATCH',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken],
            ['user_id' => 'cant-change-the-user-id']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: user_id', $data['reason_phrase']);
    }

    public function testPatchUpdateDuplicateUser()
    {
        // first account creation - should succeed
        $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );

        $response = $this->request(
            'PATCH',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken],
            ['email' => 'new-user@example.com']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Email exists', $data['reason_phrase']);
    }

    public function testPatchUpdateOtherUser()
    {
        // first account creation - should succeed
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'];

        $response = $this->request(
            'PATCH',
            '/users/'.$this->userId,
            ['access_token' => $accessToken],
            ['email' => 'non-existing-user@example.com']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testPatchUpdateUser()
    {
        $response = $this->request(
            'PATCH',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken],
            ['email' => 'new-user@example.com']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals($data['email'], 'new-user@example.com');
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('access_token', $data);
    }

    public function testPatchUpdatePassword()
    {
        $response = $this->request(
            'PATCH',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken],
            ['password' => hash('sha512', 'my-new-password')]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals($data['email'], $this->user);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('access_token', $data);
    }

    public function testDeleteInvalidQueryParameter()
    {
        $response = $this->request(
            'DELETE',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken, 'i-dont-exist' => 'some-value'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testDeleteInvalidArg()
    {
        $response = $this->request(
            'DELETE',
            '/users/not-a-valid-user-id',
            ['access_token' => $this->rootAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testDeleteWithInvalidSession()
    {
        $response = $this->request(
            'DELETE',
            '/users/'.$this->userId,
            ['access_token' => '1234567890123456789012345678901234567890'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid or expired)', $data['reason_phrase']);
    }

    public function testDeleteOtherSession()
    {
        $response = $this->request(
            'DELETE',
            '/users/'.$this->userId,
            ['access_token' => $this->testAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing access (required scope: root) for: access_token', $data['reason_phrase']);
    }

    public function testDeleteOtherUser()
    {
        // first account creation - should succeed
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => hash('sha512', 'my-new-password'),
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'];

        $response = $this->request(
            'DELETE',
            '/users/'.$this->userId,
            ['access_token' => $accessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testDeleteUser()
    {
        $response = $this->request(
            'DELETE',
            '/users/'.$this->userId,
            ['access_token' => $this->rootAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
