<?php

namespace AppBundle\EventListener;

use AppBundle\Api\ApiProblem;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\AppBundle\Controller\Api\ApiProblemException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ApiExceptionSubscriber implements EventSubscriberInterface
{

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();

        if ($e instanceof ApiProblemException) {

            $apiProblem = $e->getApiProblem();

        } else {

            $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $apiProblem = new ApiProblem($statusCode);
        }

        $response = new JsonResponse($apiProblem->toArray(), $apiProblem->getStatusCode(), ['Content-Type' => 'application/problem+json']);

        $event->setResponse($response);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}