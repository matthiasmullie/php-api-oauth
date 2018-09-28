<?php

namespace MatthiasMullie\ApiOauth\Tests\Controllers;

use MatthiasMullie\Api\TestHelpers\HttpTestCase;

class InstallTest extends HttpTestCase
{
    public function testInstall()
    {
        $this->request('POST', '/reset');
        $response = $this->request('POST', '/install');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArraySubset(['Content-Type' => ['application/json']], $response->getHeaders());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('root-app', $data['application']);
        $this->assertArrayHasKey('client_id', $data);
        $this->assertArrayHasKey('client_secret', $data);
    }
}
