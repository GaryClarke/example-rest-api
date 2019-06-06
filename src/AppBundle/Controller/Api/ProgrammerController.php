<?php

namespace AppBundle\Controller\Api;

use AppBundle\Entity\Programmer;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProgrammerController extends BaseController
{

    /**
     * @Route("/api/programmers", methods={"POST"})
     */
    public function newAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        // Deserialize data into a programmer object
        $programmer = new Programmer($data['nickname'], $data['avatarNumber']);
        $programmer->setTagLine($data['tagLine']);
        $programmer->setUser($this->findUserByUsername('weaverryan'));

        $validator = $this->get('validator');
        $errors = $validator->validate($programmer);

        $em = $this->getDoctrine()->getManager();
        $em->persist($programmer);
        $em->flush();

        $programmerUrl = $this->generateUrl(
            'api_programmers_show',
            ['nickname' => $programmer->getNickname()]
        );

        $response = $this->createApiResponse($programmer, 201, true, ['Location' => $programmerUrl]);

        return $response;
    }


    /**
     * @Route("/api/programmers/{nickname}", name="api_programmers_show", methods={"GET"})
     */
    public function showAction($nickname)
    {
        $programmer = $this->getDoctrine()->getRepository('AppBundle:Programmer')->findOneByNickname($nickname);

        if (!$programmer) {
            throw $this->createNotFoundException(sprintf(
                'No programmer found with nickname "%s"',
                $nickname
            ));
        }

        return $this->createApiResponse($programmer);
    }


    /**
     * @Route("/api/programmers", methods={"GET"})
     */
    public function listAction()
    {
        $programmers = $this->getDoctrine()->getRepository('AppBundle:Programmer')->findAll();

        return $this->createApiResponse(['programmers' => $programmers]);
    }


    /**
     * @Route("/api/programmers/{nickname}", name="api_programmers_update", methods={"PUT","PATCH"})
     */
    public function updateAction($nickname, Request $request)
    {
        $programmer = $this->getDoctrine()->getRepository('AppBundle:Programmer')->findOneByNickname($nickname);

        if (!$programmer) {
            throw $this->createNotFoundException(sprintf(
                'No programmer found with nickname "%s"',
                $nickname
            ));
        }

        $data = json_decode($request->getContent(), true);

        if (array_key_exists('avatarNumber', $data)) {
            $programmer->setAvatarNumber($data['avatarNumber']);
        }

        if (array_key_exists('tagLine', $data)) {
            $programmer->setTagLine($data['tagLine']);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($programmer);
        $em->flush();

        return $this->createApiResponse($programmer);
    }


    /**
     * @Route("/api/programmers/{nickname}", methods={"DELETE"})
     */
    public function deleteAction($nickname)
    {
        $programmer = $this->getDoctrine()->getRepository('AppBundle:Programmer')->findOneByNickname($nickname);

        if ($programmer) {

            $em = $this->getDoctrine()->getManager();
            $em->remove($programmer);
            $em->flush();
        }

        return $this->createApiResponse(null, 204);
    }
}