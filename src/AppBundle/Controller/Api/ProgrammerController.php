<?php

namespace AppBundle\Controller\Api;

use AppBundle\Api\ApiProblem;
use AppBundle\Entity\Programmer;
use AppBundle\Api\ApiProblemException;
use AppBundle\Controller\BaseController;
use AppBundle\Pagination\PaginatedCollection;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class ProgrammerController extends BaseController
{

    private static $excludedFromUpdate = ['id', 'nickname'];

    /**
     * @Route("/api/programmers", methods={"POST"})
     */
    public function newAction(Request $request, SerializerInterface $serializer)
    {
        $data = $request->getContent();

        if (!$this->isJson($data)) {

            $apiProblem = new ApiProblem(400, ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT);

            throw new ApiProblemException($apiProblem);
        }

        $programmer = $serializer->deserialize($data, Programmer::class, 'json');

        $programmer->setUser($this->findUserByUsername('weaverryan'));

        $validator = $this->get('validator');

        $validation = $validator->validate($programmer);

        if ($validation->count() > 0) {

            $this->throwApiProblemValidationException($validation);
        }

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


    public function isJson($string)
    {
        json_decode($string);

        return (json_last_error() === JSON_ERROR_NONE);
    }


    /**
     * @param $validationErrors ConstraintViolationList
     * @return array
     */
    private function getErrors($validationErrors)
    {
        $errors = [];

        foreach ($validationErrors as $validationError) {

            $errors[$validationError->getPropertyPath()][] = $validationError->getMessage();
        }

        return $errors;
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
     * @Route("/api/programmers", name="api_programmers_collection", methods={"GET"})
     */
    public function listAction(Request $request)
    {
        $page = $request->query->get('page', 1);

        $query = $this->getDoctrine()->getRepository('AppBundle:Programmer')->findAllQueryBuilder();

        $adapter = new DoctrineORMAdapter($query);

        $pagerfanta = new Pagerfanta($adapter);

        if ($page == 2) {

            dump($pagerfanta->getNbPages()); die();
        }

        $pagerfanta->setMaxPerPage(10);

        $pagerfanta->setCurrentPage($page);

        $programmers = [];

        foreach ($pagerfanta->getCurrentPageResults() as $result) {
            $programmers[] = $result;
        }

        $paginatedCollection = new PaginatedCollection($programmers, $pagerfanta->getNbResults());

        $route = 'api_programmers_collection';
        $routeParams = [];
        $createLinkUrl = function ($targetPage) use ($route, $routeParams) {
            return $this->generateUrl($route, array_merge($routeParams, ['page' => $targetPage]));
        };

        $paginatedCollection->addLink('self', $createLinkUrl($page));
        $paginatedCollection->addLink('first', $createLinkUrl(1));
        $paginatedCollection->addLink('last', $createLinkUrl($pagerfanta->getNbPages()));

        if ($pagerfanta->hasNextPage()) {
            $paginatedCollection->addLink('next', $createLinkUrl($pagerfanta->getNextPage()));
        }

        if ($pagerfanta->hasPreviousPage()) {
            $paginatedCollection->addLink('prev', $pagerfanta->getPreviousPage());
        }

        return $this->createApiResponse($paginatedCollection);

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

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($data as $key => $value) {

            if (!in_array($key, static::$excludedFromUpdate)) {

                $accessor->setValue($programmer, $key, $value);
            }
        }

        $validator = $this->get('validator');

        $validation = $validator->validate($programmer);

        if ($validation->count() > 0) {

            $this->throwApiProblemValidationException($validation);
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


    private function throwApiProblemValidationException($validation)
    {
        $errors = $this->getErrors($validation);

        $apiProblem = new ApiProblem(400, ApiProblem::TYPE_VALIDATION_ERROR);

        $apiProblem->set('errors', $errors);

        throw new ApiProblemException($apiProblem);
    }


    private function createInvalidRequestBodyErrorResponse()
    {
        $apiProblem = new ApiProblem(400, ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT);

//        throw new HttpException(400, 'Invalid Json body');

        return new JsonResponse($apiProblem->toArray(), $apiProblem->getStatusCode(), ['Content-Type' => 'application/problem+json']);
    }
}