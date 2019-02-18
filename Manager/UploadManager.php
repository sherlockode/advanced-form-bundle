<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Sherlockode\AdvancedFormBundle\Event\RemoveUploadEvent;
use Sherlockode\AdvancedFormBundle\Event\UploadEvent;
use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Sherlockode\AdvancedFormBundle\Storage\StorageInterface;
use Sherlockode\AdvancedFormBundle\UploadHandler\UploadHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccess;

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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

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
     * @param ObjectManager            $om
     * @param MappingManager           $mappingManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param StorageInterface         $tmpStorage
     * @param string|null              $tmpUploadedFileClass
     */
    public function __construct(
        ObjectManager $om,
        MappingManager $mappingManager,
        EventDispatcherInterface $eventDispatcher,
        StorageInterface $tmpStorage,
        $tmpUploadedFileClass = null
    ) {
        $this->om = $om;
        $this->mappingManager = $mappingManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->tmpStorage = $tmpStorage;
        $this->tmpUploadedFileClass = $tmpUploadedFileClass;
    }

    public function addHandler(UploadHandlerInterface $handler, $code)
    {
        $this->handlers[$code] = $handler;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string|null  $type
     * @param int|null     $id
     *
     * @return object
     * @throws \Exception
     */
    public function upload(UploadedFile $uploadedFile, $type, $id = null)
    {
        $mapping = $this->mappingManager->getMapping($type);
        $entityClass = $mapping->class;
        $containerEntityClass = $mapping->fileClass;
        $isMultiple = $mapping->multiple;

        if ($id === null) {
            $subject = new $entityClass();
        } else {
            $subject = $this->om->getRepository($entityClass)->find($id);
            if (null === $subject) {
                throw new \Exception(sprintf('Cannot find object of type "%s" with id %s.', $type, $id));
            }
        }
        if ($isMultiple) {
            $fileContainer = new $containerEntityClass();
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $files = $propertyAccessor->getValue($subject, $mapping->fileCollectionProperty);
            if ($files instanceof \Traversable) {
                $files = iterator_to_array($files);
            }
            $files[] = $fileContainer;
            $propertyAccessor->setValue($subject, $mapping->fileCollectionProperty, $files);
            $subject = $fileContainer;
        }

        $handler = $this->getHandler($mapping, $subject);
        $handler->upload($subject, $mapping->fileProperty, $uploadedFile);
        $this->eventDispatcher->dispatch('afb.post_upload', new UploadEvent($subject, $mapping, $uploadedFile));

        $this->om->persist($subject);
        $this->om->flush();

        return $subject;
    }

    /**
     * @param UploadedFile $uploadedFile
     *
     * @return TemporaryUploadedFileInterface
     * @throws \Exception
     */
    public function uploadTemporary(UploadedFile $uploadedFile)
    {
        $key = sha1(microtime(true) . rand())  . '.' . $uploadedFile->guessExtension();
        $this->tmpStorage->write($key, file_get_contents($uploadedFile->getPathname()));

        $class = $this->tmpUploadedFileClass;
        if (!$class || !class_exists($class)) {
            throw new \Exception('The class to use for temporary file upload has not been defined');
        }
        /** @var TemporaryUploadedFileInterface $obj */
        $obj = new $class();
        $obj->setKey($key);
        $obj->setToken(rand());
        $obj->setFilename($uploadedFile->getClientOriginalName());

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
            $mapping = $this->mappingManager->getMapping($type);
            $subject = $this->om->getRepository($mapping->fileClass)->find($id);
            if (null === $subject) {
                throw new \Exception(sprintf('Cannot find object of type "%s" with id %s.', $type, $id));
            }
            $handler = $this->getHandler($mapping, $subject);
            $field = $mapping->fileProperty;
            $handler->remove($subject, $field);
            $this->eventDispatcher->dispatch('afb.post_remove_upload', new RemoveUploadEvent($subject, $mapping));
            if ($deleteObject || $mapping->multiple) {
                $this->om->remove($subject);
            }
            $this->om->flush();
        }
    }

    /**
     * @param Mapping $mapping
     * @param object  $subject
     *
     * @return string
     */
    public function getFilename(Mapping $mapping, $subject)
    {
        $handler = $this->getHandler($mapping, $subject);
        $field = $mapping->fileProperty;

        return $handler->getFilename($subject, $field);
    }

    /**
     * @param Mapping $mapping
     * @param object  $subject
     *
     * @return UploadHandlerInterface
     * @throws \RuntimeException
     */
    private function getHandler(Mapping $mapping, $subject)
    {
        $handlerCode = $mapping->handler;

        if (!isset($this->handlers[$handlerCode])) {
            throw new \RuntimeException(sprintf('Unknown handler "%s"', $handlerCode));
        }
        $handler = $this->handlers[$handlerCode];
        $field = $mapping->fileProperty;
        if (!$handler->supports($subject, $field)) {
            throw new \RuntimeException(sprintf('%s does not support this object', get_class($handler)));
        }

        return $handler;
    }
}
