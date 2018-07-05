<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Sherlockode\AdvancedFormBundle\UploadHandler\UploadHandlerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadManager
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var MappingManager
     */
    private $mappingManager;

    /**
     * @var UploadHandlerInterface[]
     */
    private $handlers = [];

    /**
     * UploadManager constructor.
     *
     * @param ObjectManager    $om
     * @param MappingManager   $mappingManager
     */
    public function __construct(ObjectManager $om, MappingManager $mappingManager)
    {
        $this->om = $om;
        $this->mappingManager = $mappingManager;
    }

    public function addHandler(UploadHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string|null  $type
     * @param int|null     $id
     *
     * @throws \Exception
     */
    public function upload(UploadedFile $uploadedFile, $type = null, $id = null)
    {
        if (null !== $type && null !== $id) {
            $entityClass = $this->mappingManager->getMappedEntity($type);
            $subject = $this->om->getRepository($entityClass)->find($id);
            if (null === $subject) {
                throw new \Exception(sprintf('Cannot find object of type "%s" with id %s.', $type, $id));
            }
            $field = $this->mappingManager->getFileProperty($type);
            foreach ($this->handlers as $handler) {
                if ($handler->supports($subject, $field)) {
                    $handler->upload($subject, $field, $uploadedFile);
                    break;
                }
            }
            $this->om->flush();

            return;
        }

        throw new \Exception('Missing data');
    }

    /**
     * @param string $type
     * @param int    $id
     * @param bool   $deleteObject
     *
     * @throws \Exception
     */
    public function remove($type, $id, $deleteObject = false)
    {
        if (null !== $type && null !== $id) {
            $entityClass = $this->mappingManager->getMappedEntity($type);
            $subject = $this->om->getRepository($entityClass)->find($id);
            if (null === $subject) {
                throw new \Exception(sprintf('Cannot find object of type "%s" with id %s.', $type, $id));
            }
            $field = $this->mappingManager->getFileProperty($type);
            foreach ($this->handlers as $handler) {
                if ($handler->supports($subject, $field)) {
                    $handler->remove($subject, $field);
                    break;
                }
            }
            if ($deleteObject) {
                $this->om->remove($subject);
            }
            $this->om->flush();
        }
    }
}
