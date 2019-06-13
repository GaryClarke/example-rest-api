<?php

namespace AppBundle\Controller\Api;

use AppBundle\Api\ApiProblem;
use AppBundle\Entity\Programmer;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Tests\AppBundle\Controller\Api\ApiProblemException;

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


    public function isJson($string) {
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


//    /**
//     * Return calculation based on claim type.
//     *
//     * @Route(
//     *     "/{claimType}",
//     *     name="calculate",
//     *     methods={"GET","HEAD"},
//     *     requirements={"claimType"="MAT|DTH|SUR|PUP"}
//     * )
//     *
//     * @param Request $request
//     * @param IntegroApiInterface $integroApi
//     * @param SerializerInterface $serializer
//     * @param string $claimType
//     *
//     *
//     * @return JsonResponse
//     */
//    public function calculateAction(
//        Request $request,
//        IntegroApiInterface $integroApi,
//        SerializerInterface $serializer,
//        $claimType
//    ) {
//        // Fetch policy data from Integro
//        $data = $integroApi->fetchPolicyData($request->get('policy_number'), $request->get('company_code'));
//
//        $policyData = $serializer->deserialize($data, PolicyData::class, 'json');
//
//        // Get the required calculator based upon claim type, profit status, value status and company code (source system)
//        $calculator = CalculatorFactory::createCalculator($claimType, $policyData);
//
//        // Validate @todo - come back to this
////        $errors = $calculator->validate();
//
////        if (count($errors) > 0) {
////            /*
////             * Uses a __toString method on the $errors variable which is a
////             * ConstraintViolationList object. This gives us a nice string
////             * for debugging.
////             */
////            $errorsString = (string) $errors;
////
////            return new JsonResponse($errorsString, 422);
////        }
//
//        $em = $this->getDoctrine()->getManager();
//
//        // Perform the calcultion
//        $calculation = $calculator->setEntityManager($em)->calculate();
//
//        // Have to use this in order to return values as snake_case because the serializer converts everything to camelcase
//        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
//
//        // Return the response as JSON
//        return new JsonResponse($normalizer->normalize($calculation->getPolicyData()));
//    }


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