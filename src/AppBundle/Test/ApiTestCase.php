<?php

namespace AppBundle\Test;

use AppBundle\Entity\Programmer;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ApiTestCase extends WebTestCase
{
    protected $container;

    protected $client;

    private $responseAsserter;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->container = $this->client->getContainer();

        DatabasePrimer::prime(self::$kernel);

        $this->entityManager = $this->container->get('doctrine')->getManager();

        $this->entityManager->beginTransaction();

        $this->createUser('weaverryan');
    }


    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->entityManager->rollback();
        $this->entityManager->close();
        $this->entityManager = null;
        parent::tearDown();
    }


    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }


    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine.orm.entity_manager');
    }


    protected function asserter()
    {
        if ($this->responseAsserter === null) {
            $this->responseAsserter = new ResponseAsserter();
        }
        return $this->responseAsserter;
    }


    protected function assertJsonResponse($response, $statusCode = 200)
    {
        $this->assertEquals(
            $statusCode, $response->getStatusCode(),
            $response->getContent()
        );
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            $response->headers
        );
    }


    /**
     * Send a json request to a given endpoint
     *
     * @param $verb
     * @param $endpoint
     * @param array $data
     * @return Crawler
     */
    protected function jsonRequest($verb, $endpoint, array $data = array())
    {
        $data = empty($data) ? null : json_encode($data);

        return $this->client->request($verb, $endpoint,
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json',
                'CONTENT_TYPE' => 'application/json'
            ),
            $data
        );
    }


    protected function getService($id)
    {
        return self::$kernel->getContainer()
            ->get($id);
    }


    protected function createUser($username, $plainPassword = 'foo')
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username . '@foo.com');
        $password = $this->getService('security.password_encoder')
            ->encodePassword($user, $plainPassword);
        $user->setPassword($password);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createProgrammer(array $data)
    {
        $data = array_merge(array(
            'powerLevel' => rand(0, 10),
            'user'       => $this->getEntityManager()->getRepository('AppBundle:User')->findAny(),
        ), $data);

        $accessor = PropertyAccess::createPropertyAccessor();

        $programmer = new Programmer();

        foreach ($data as $key => $value) {
            $accessor->setValue($programmer, $key, $value);
        }

        $this->getEntityManager()->persist($programmer);
        $this->getEntityManager()->flush();
        return $programmer;
    }
}