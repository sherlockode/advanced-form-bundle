<?php

namespace Sherlockode\AdvancedFormBundle\Event;

use Sherlockode\AdvancedFormBundle\DependentEntity\DependentMapperInterface;
use Symfony\Component\HttpFoundation\Response;

class GetResponseDependentResultEvent
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var object
     */
    private $entity;

    /**
     * @var DependentMapperInterface
     */
    private $mapper;

    /**
     * @param DependentMapperInterface $mapper
     * @param object                   $entity
     */
    public function __construct(DependentMapperInterface $mapper, $entity)
    {
        $this->mapper = $mapper;
        $this->entity = $entity;
    }

    /**
     * @return Response
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

    public function getEntity()
    {
        return $this->entity;
    }

    public function getMapper()
    {
        return $this->mapper;
    }
}
