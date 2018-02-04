<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Vich\UploaderBundle\Handler\UploadHandler;

class UploadManager
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var UploadHandler
     */
    private $uploadHandler;

    /**
     * @var MappingManager
     */
    private $mappingManager;

    /**
     * UploadManager constructor.
     *
     * @param ObjectManager  $om
     * @param UploadHandler  $uploadHandler
     * @param MappingManager $mappingManager
     */
    public function __construct(ObjectManager $om, UploadHandler $uploadHandler, MappingManager $mappingManager)
    {
        $this->om = $om;
        $this->uploadHandler = $uploadHandler;
        $this->mappingManager = $mappingManager;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string|null  $type
     * @param int|null     $id
     *
     * @throws \Exception
     *
     * @return File
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
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $propertyAccessor->setValue($subject, $field, $uploadedFile);
            $this->uploadHandler->upload($subject, $field);
            $this->om->flush();

            return $propertyAccessor->getValue($subject, $field);
        }

        return $uploadedFile->move(sys_get_temp_dir());
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
            $this->uploadHandler->remove($subject, $field);
            if ($deleteObject) {
                $this->om->remove($subject);
            }
            $this->om->flush();
        }
    }
}
