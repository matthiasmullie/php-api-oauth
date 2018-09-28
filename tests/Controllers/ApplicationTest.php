<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

class ApplicationTest extends BaseTestCase
{
    public function testBadMethod()
    {
        $response = $this->request('PATCH', '/applications/new-app');
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(405, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Method Not Allowed', $data['reason_phrase']);
    }

    public function testGetInvalidQueryParameter()
    {
        $response = $this->request(
            'GET',
            '/applications/non-existing-client-id',
            ['access_token' => $this->rootAccessToken, 'i-dont-exist' => 'some-value'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testGetNonExistingApplication()
    {
        $response = $this->request(
            'GET',
            '/applications/non-existing-client-id',
            ['access_token' => $this->rootAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Not Found', $data['reason_phrase']);
    }

    public function testGetExistingApplicationForNonOwner()
    {
        // create a new user that has nothing to do with our test application
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => 'my-password',
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'];

        $response = $this->request(
            'GET',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $accessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid user session)', $data['reason_phrase']);
    }

    public function testGetApplicationFromRootSession()
    {
        $response = $this->request(
            'GET',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals($this->testApplication, $data['application']);
        $this->assertEquals($this->testApplicationClientId, $data['client_id']);
        $this->assertEquals($this->testApplicationClientSecret, $data['client_secret']);
        $this->assertEquals($this->userId, $data['user_id']);
    }

    public function testGetApplicationFromAppSession()
    {
        $response = $this->request(
            'GET',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->testAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid application session)', $data['reason_phrase']);
    }

    public function testPostInvalidQueryParameter()
    {
        $response = $this->request(
            'POST',
            '/applications',
            ['access_token' => $this->rootAccessToken, 'i-dont-exist' => 'some-value'],
            ['application' => 'new-app']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testPostNoSession()
    {
        $response = $this->request(
            'POST',
            '/applications',
            [],
            ['application' => 'new-app']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: access_token', $data['reason_phrase']);
    }

    public function testPostInvalidSession()
    {
        $response = $this->request(
            'POST',
            '/applications',
            ['access_token' => '1234567890123456789012345678901234567890'],
            ['application' => 'new-app']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid or expired)', $data['reason_phrase']);
    }

    public function testPostOtherSession()
    {
        $response = $this->request(
            'POST',
            '/applications',
            ['access_token' => $this->testAccessToken],
            ['application' => 'new-app']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid application session)', $data['reason_phrase']);
    }

    public function testPostDuplicateApplication()
    {
        // create a new user that has nothing to do with our test application
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => 'my-password',
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $accessToken = $data['access_token'];

        // see if this new user can create an already existing application
        // (if the actual owner would try this, it would succeed - he can alter...)
        $response = $this->request(
            'POST',
            '/applications',
            ['access_token' => $accessToken],
            ['application' => $this->testApplication]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Application exists', $data['reason_phrase']);
    }

    public function testPostTooManyApplications()
    {
        // create too many applications - the first ones should succeed,
        // but eventually, we'll end up with failures
        for ($i = 0; $i < 25; $i++) {
            $response = $this->request(
                'POST',
                '/applications',
                ['access_token' => $this->rootAccessToken],
                ['application' => 'test-app-'.$i]
            );
        }
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('User has too many applications', $data['reason_phrase']);
    }

    public function testPostNewApplication()
    {
        $response = $this->request(
            'POST',
            '/applications',
            ['access_token' => $this->rootAccessToken],
            ['application' => 'new-app']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('new-app', $data['application']);
        $this->assertArrayHasKey('client_id', $data);
        $this->assertArrayHasKey('client_secret', $data);
    }

    public function testPutInvalidQueryParameter()
    {
        $response = $this->request(
            'PUT',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken, 'i-dont-exist' => 'some-value'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testPutNonExistingApplication()
    {
        $response = $this->request(
            'PUT',
            '/applications/non-existing-client-id',
            ['access_token' => $this->rootAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Not Found', $data['reason_phrase']);
    }

    public function testPutNoSession()
    {
        $response = $this->request(
            'PUT',
            '/applications/'.$this->testApplicationClientId,
            [],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: access_token', $data['reason_phrase']);
    }

    public function testPutInvalidSession()
    {
        $response = $this->request(
            'PUT',
            '/applications/'.$this->testApplicationClientId,
            ['access_token' => '1234567890123456789012345678901234567890'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid or expired)', $data['reason_phrase']);
    }

    public function testPutOtherSession()
    {
        $response = $this->request(
            'PUT',
            '/applications/'.$this->testApplicationClientId,
            ['access_token' => $this->testAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid application session)', $data['reason_phrase']);
    }

    public function testPutDuplicateApplication()
    {
        $this->request(
            'POST',
            '/applications',
            ['access_token' => $this->rootAccessToken],
            ['application' => 'new-app']
        );

        $response = $this->request(
            'PUT',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            ['application' => 'new-app']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Application exists', $data['reason_phrase']);
    }

    public function testPutTooManyApplications()
    {
        // create a new user that has nothing to do with our test application, yet
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => 'my-password',
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $userId = $data['user_id'];
        $accessToken = $data['access_token'];

        // create too many applications - the first ones should succeed,
        // but eventually, we'll end up with failures
        for ($i = 0; $i < 25; $i++) {
            $this->request(
                'POST',
                '/applications',
                ['access_token' => $accessToken],
                ['application' => 'test-app-'.$i]
            );
        }

        // now see if we can change the owner of an existing one, to an
        // owner that has too many applications already
        $response = $this->request(
            'PUT',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            ['user_id' => $userId]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('User has too many applications', $data['reason_phrase']);
    }

    public function testPutInvalidUser()
    {
        $response = $this->request(
            'PUT',
            '/applications/'.$this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            ['user_id' => '1234567890123456789012345678901234567890']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid user', $data['reason_phrase']);
    }

    public function testPutUpdateApplicationClientId()
    {
        $response = $this->request(
            'PUT',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            ['client_id' => 'cant-change-the-client-id']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: client_id', $data['reason_phrase']);
    }

    public function testPutUpdateApplicationClientSecret()
    {
        $response = $this->request(
            'PUT',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            ['client_secret' => 'cant-change-the-client-secret']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: client_secret', $data['reason_phrase']);
    }

    public function testPutUpdateApplicationName()
    {
        $response = $this->request(
            'PUT',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            ['application' => 'new-app']
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('new-app', $data['application']);
        $this->assertEquals($this->userId, $data['user_id']);
        $this->assertEquals($this->testApplicationClientId, $data['client_id']);
        $this->assertEquals($this->testApplicationClientSecret, $data['client_secret']);
    }

    public function testPutUpdateApplicationUser()
    {
        // create a new user that has nothing to do with our test application, yet
        $response = $this->request(
            'POST',
            '/users',
            [],
            [
                'email' => 'new-user@example.com',
                'password' => 'my-password',
                'client_id' => $this->rootApplicationClientId,
                'client_secret' => $this->rootApplicationClientSecret,
            ]
        );
        $data = json_decode((string) $response->getBody(), true);
        $userId = $data['user_id'];

        // update application, set new owner
        $response = $this->request(
            'PUT',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            ['user_id' => $userId]
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals($userId, $data['user_id']);
        $this->assertEquals($this->testApplication, $data['application']);
        $this->assertEquals($this->testApplicationClientId, $data['client_id']);
        $this->assertEquals($this->testApplicationClientSecret, $data['client_secret']);
    }

    public function testDeleteInvalidQueryParameter()
    {
        $response = $this->request(
            'DELETE',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken, 'i-dont-exist' => 'some-value'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: i-dont-exist', $data['reason_phrase']);
    }

    public function testDeleteNonExistingApplication()
    {
        $response = $this->request(
            'DELETE',
            '/applications/non-existing-client-id',
            ['access_token' => $this->rootAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Not Found', $data['reason_phrase']);
    }

    public function testDeleteNoSession()
    {
        $response = $this->request(
            'DELETE',
            '/applications/'.$this->testApplicationClientId,
            [],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Missing: access_token', $data['reason_phrase']);
    }

    public function testDeleteApplicationInvalidSession()
    {
        $response = $this->request(
            'DELETE',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => '1234567890123456789012345678901234567890'],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid or expired)', $data['reason_phrase']);
    }

    public function testDeleteApplicationOtherSession()
    {
        $response = $this->request(
            'DELETE',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->testAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Invalid: access_token (invalid application session)', $data['reason_phrase']);
    }

    public function testDeleteApplication()
    {
        // update application, set new owner
        $response = $this->request(
            'DELETE',
            '/applications/' . $this->testApplicationClientId,
            ['access_token' => $this->rootAccessToken],
            []
        );
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
