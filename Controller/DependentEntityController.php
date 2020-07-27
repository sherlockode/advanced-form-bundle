<?php

namespace Sherlockode\AdvancedFormBundle\Controller;

use Sherlockode\AdvancedFormBundle\DependentEntity\DependentMapperPool;
use Sherlockode\AdvancedFormBundle\Event\GetResponseDependentResultEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

class DependentEntityController extends AbstractController
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var DependentMapperPool
     */
    private $mapperPool;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param DependentMapperPool      $mapperPool
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, DependentMapperPool $mapperPool)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->mapperPool = $mapperPool;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function getDependentResultsAction(Request $request)
    {
        $id = (int) $request->get('id');
        $mapperName = $request->get('mapper');
        $mapper = $this->mapperPool->getMapper($mapperName);

        $entity = $this->getDoctrine()->getRepository($mapper->getSubjectClass())->find($id);
        if ($entity === null) {
            throw $this->createNotFoundException();
        }

        $event = new GetResponseDependentResultEvent($mapper, $entity);
        if (Kernel::VERSION_ID < 40300) {
            $this->eventDispatcher->dispatch(get_class($event), $event);
        } else {
            $this->eventDispatcher->dispatch($event);
        }
        if ($event->getResponse()) {
            return $event->getResponse();
        }

        $data = $mapper->getDependentResults($entity);

        return new JsonResponse($data);
    }
}
