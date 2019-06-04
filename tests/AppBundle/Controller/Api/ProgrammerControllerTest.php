<?php

namespace Tests\AppBundle\Controller\Api;

use Tests\ApiTestCase;

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


    public function testGETProgrammersCollection()
    {
        $this->createProgrammer([
            'nickname'     => 'UnitTester',
            'avatarNumber' => 3,
        ]);

        $this->createProgrammer([
            'nickname'     => 'CowboyCoder',
            'avatarNumber' => 5,
        ]);

        $this->jsonRequest('GET', '/api/programmers');

        $response = $this->getClient()->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyIsArray($response, 'programmers');
        $this->asserter()->assertResponsePropertyCount($response, 'programmers', 2);
        $this->asserter()->assertResponsePropertyEquals($response, 'programmers[1].nickname', 'CowboyCoder');
    }


    public function testPUTProgrammer()
    {
        $this->createProgrammer([
            'nickname'     => 'CowboyCoder',
            'avatarNumber' => 5,
            'tagLine'      => 'foo'
        ]);

        $data = array(
            'nickname'     => 'CowgirlCoder',
            'avatarNumber' => 2,
            'tagLine'      => 'foo'
        );

        $this->jsonRequest('PUT', '/api/programmers/CowboyCoder', $data);

        $response = $this->getClient()->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $this->asserter()->assertResponsePropertyEquals($response, 'avatarNumber', 2);
        $this->asserter()->assertResponsePropertyEquals($response, 'nickname', 'CowboyCoder');
    }


    public function testDELETEProgrammer()
    {
        $this->createProgrammer(array(
            'nickname'     => 'UnitTester',
            'avatarNumber' => 3,
        ));

        $this->jsonRequest('DELETE', '/api/programmers/UnitTester');

        $response = $this->getClient()->getResponse();

        $this->assertEquals(204, $response->getStatusCode());
    }


    public function testPATCHProgrammer()
    {
        $this->createProgrammer([
            'nickname'     => 'CowboyCoder',
            'avatarNumber' => 5,
            'tagLine'      => 'foo'
        ]);

        $data = array(
            'tagLine'      => 'bar'
        );

        $this->jsonRequest('PATCH', '/api/programmers/CowboyCoder', $data);

        $response = $this->getClient()->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $this->asserter()->assertResponsePropertyEquals($response, 'avatarNumber', 5);
        $this->asserter()->assertResponsePropertyEquals($response, 'tagLine', 'bar');
    }
}