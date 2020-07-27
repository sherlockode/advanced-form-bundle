<?php

namespace Sherlockode\AdvancedFormBundle\Event;

use Symfony\Component\HttpFoundation\Response;

class GetResponseRemoveFileEvent
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var string
     */
    private $type;

    /**
     * @var object
     */
    private $entity;

    /**
     * @param string $type
     * @param object $entity
     */
    public function __construct(string $type, $entity)
    {
        $this->type = $type;
        $this->entity = $entity;
    }

    /**
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
