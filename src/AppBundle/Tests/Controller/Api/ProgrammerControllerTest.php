<?php

namespace AppBundle\Tests\Controller\Api;

use AppBundle\Test\ApiTestCase;

class ProgrammerControllerTest extends ApiTestCase
{

    public function testPOST()
    {
        $data = array(
            'nickname'     => 'ObjectOrienter',
            'avatarNumber' => 5,
            'tagLine'      => 'a test dev!'
        );

        // 1) POST - create a programmer resource
        $this->jsonRequest('POST', '/api/programmers', $data);

        $response = $this->getClient()->getResponse();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('/api/programmers/ObjectOrienter', $response->headers->get('location'));
        $finishedData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('nickname', $finishedData);
        $this->assertEquals('ObjectOrienter', $finishedData['nickname']);
    }

    public function testGETProgrammer()
    {
        $this->createProgrammer(array(
            'nickname'     => 'UnitTester',
            'avatarNumber' => 3,
        ));

        $this->jsonRequest('GET', '/api/programmers/UnitTester');

        $response = $this->getClient()->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $this->asserter()->assertResponsePropertiesExist($response, array(
            'nickname',
            'avatarNumber',
            'powerLevel',
            'tagLine'
        ));

        $this->asserter()->assertResponsePropertyEquals($response, 'nickname', 'UnitTester');
    }
}
