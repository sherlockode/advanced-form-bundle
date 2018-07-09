<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Sherlockode\AdvancedFormBundle\Storage\StorageInterface;
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
     * @var StorageInterface
     */
    private $tmpStorage;

    private $tmpUploadedFileClass;

    /**
     * @var UploadHandlerInterface[]
     */
    private $handlers = [];

    /**
     * UploadManager constructor.
     *
     * @param ObjectManager    $om
     * @param MappingManager   $mappingManager
     * @param StorageInterface $tmpStorage
     * @param string|null      $tmpUploadedFileClass
     */
    public function __construct(ObjectManager $om, MappingManager $mappingManager, StorageInterface $tmpStorage, $tmpUploadedFileClass = null)
    {
        $this->om = $om;
        $this->mappingManager = $mappingManager;
        $this->tmpStorage = $tmpStorage;
        $this->tmpUploadedFileClass = $tmpUploadedFileClass;
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
     * @param UploadedFile $uploadedFile
     *
     * @return TemporaryUploadedFileInterface
     * @throws \Exception
     */
    public function uploadTemporary(UploadedFile $uploadedFile)
    {
        $newFile = $this->tmpStorage->write($uploadedFile);

        $class = $this->tmpUploadedFileClass;
        if (!$class || !class_exists($class)) {
            throw new \Exception('The class to use for temporary file upload has not been defined');
        }
        $obj = new $class();
        $obj->setKey($newFile->getFilename());
        $obj->setToken(rand());

        $this->om->persist($obj);
        $this->om->flush($obj);

        return $obj;
    }

    /**
     * @param TemporaryUploadedFileInterface $fileInfo
     */
    public function removeTemporary(TemporaryUploadedFileInterface $fileInfo)
    {
        $this->tmpStorage->remove($fileInfo->getKey());
        $this->om->remove($fileInfo);
        $this->om->flush($fileInfo);
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
