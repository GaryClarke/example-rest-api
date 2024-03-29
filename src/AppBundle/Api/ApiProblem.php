<?php

namespace AppBundle\Api;

use Symfony\Component\HttpFoundation\Response;

/**
 * A wrapper for holding data to be used for a application/problem+json response
 */
class ApiProblem
{
    const TYPE_VALIDATION_ERROR = 'validation_error';
    const TYPE_INVALID_REQUEST_BODY_FORMAT = 'invalid_body_format';

    private static $titles = [
        self::TYPE_VALIDATION_ERROR            => 'There was a validation error',
        self::TYPE_INVALID_REQUEST_BODY_FORMAT => 'Invalid JSON format sent',
    ];

    private $statusCode;

    private $type;

    private $title;

    private $extraData = array();
    

    public function __construct($statusCode, $type = null)
    {        
        $this->statusCode = $statusCode;

        if ($type === null) { // If no type, set to about:blank

            $type = 'about:blank';

            $title = isset(Response::$statusTexts[$statusCode]) ? Response::$statusTexts[$statusCode] : 'Unknown status code :(';

        } else {
            

            if (!isset(self::$titles[$type])) {

                throw new \InvalidArgumentException('No title for type ' . $type);
            }

            $title = self::$titles[$type];
        }

        $this->title = $title;
        $this->type = $type;
    }

    public function toArray()
    {
        return array_merge(
            $this->extraData,
            array(
                'status' => $this->statusCode,
                'type'   => $this->type,
                'title'  => $this->title,
            )
        );
    }

    public function set($name, $value)
    {
        $this->extraData[$name] = $value;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
