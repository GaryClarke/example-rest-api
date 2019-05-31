<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('weaverryan');
        $user->setEmail('ryan@knplabs.com');
        $user->setPassword('$2a$08$Y1J2TZ6KMp7pA4MYmq4oW.S.RN8E9dXqFTUHVQtCWyTTnU37akcrW');

        $manager->persist($user);

        $manager->flush();
    }
}