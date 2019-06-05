<?php

namespace AppBundle\Controller;

use AppBundle\Repository\ProgrammerRepository;
use AppBundle\Repository\UserRepository;
use AppBundle\Repository\ProjectRepository;
use AppBundle\Repository\BattleRepository;
use AppBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class BaseController extends Controller
{
    /** @var Serializer */
    private $serializer;

    public function __construct()
    {
        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes(['id']);
        $encoder = new JsonEncoder();

        $this->serializer = new Serializer([$normalizer], [$encoder]);
    }

    /**
     * Is the current user logged in?
     *
     * @return boolean
     */
    public function isUserLoggedIn()
    {
        return $this->container->get('security.authorization_checker')
            ->isGranted('IS_AUTHENTICATED_FULLY');
    }

    /**
     * Logs this user into the system
     *
     * @param User $user
     */
    public function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());

        $this->container->get('security.token_storage')->setToken($token);
    }

    public function addFlash($message, $positiveNotice = true)
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $noticeKey = $positiveNotice ? 'notice_happy' : 'notice_sad';

        $request->getSession()->getFlashbag()->add($noticeKey, $message);
    }

    /**
     * Used to find the fixtures user - I use it to cheat in the beginning
     *
     * @param $username
     * @return User
     */
    public function findUserByUsername($username)
    {
        return $this->getUserRepository()->findUserByUsername($username);
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:User');
    }

    /**
     * @return ProgrammerRepository
     */
    protected function getProgrammerRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Programmer');
    }

    /**
     * @return ProjectRepository
     */
    protected function getProjectRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Project');
    }

    /**
     * @return BattleRepository
     */
    protected function getBattleRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Battle');
    }

    /**
     * @return \AppBundle\Battle\BattleManager
     */
    protected function getBattleManager()
    {
        return $this->container->get('battle.battle_manager');
    }

    /**
     * @return ApiTokenRepository
     */
    protected function getApiTokenRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:ApiToken');
    }

    /**
     * @param $data
     * @param string $format
     * @return bool|float|int|string
     */
    protected function serialize($data, $format = 'json')
    {
//        return $this->container->get('serializer')->serialize($data, $format);

        return $this->serializer->serialize($data, $format);
    }


    /**
     * @param $data
     * @param int $statusCode
     * @param bool $alreadyJson
     * @param array $headers
     * @return JsonResponse
     */
    protected function createApiResponse($data, $statusCode = 200, $alreadyJson = true, $headers = [])
    {
        $json = $this->serialize($data);

        return new JsonResponse($json, $statusCode, $headers, $alreadyJson);
    }
}
