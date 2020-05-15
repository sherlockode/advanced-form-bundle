<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Sherlockode\AdvancedFormBundle\Storage\StorageInterface;
use Sherlockode\AdvancedFormBundle\UploadHandler\UploadHandlerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccess;

class UploadManager
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

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
     * @param EntityManagerInterface   $em
     * @param StorageInterface         $tmpStorage
     * @param string|null              $tmpUploadedFileClass
     */
    public function __construct(
        EntityManagerInterface $em,
        StorageInterface $tmpStorage,
        $tmpUploadedFileClass = null
    ) {
        $this->em = $em;
        $this->tmpStorage = $tmpStorage;
        $this->tmpUploadedFileClass = $tmpUploadedFileClass;
    }

    public function addHandler(UploadHandlerInterface $handler, $code)
    {
        $this->handlers[$code] = $handler;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Mapping      $mapping
     * @param int|null     $id
     *
     * @return object
     * @throws \Exception
     */
    public function upload(UploadedFile $uploadedFile, Mapping $mapping, $id = null)
    {
        $entityClass = $mapping->class;

        if ($id === null) {
            $subject = new $entityClass();
        } else {
            $subject = $this->em->getRepository($entityClass)->find($id);
            if (null === $subject) {
                throw new \Exception(sprintf('Cannot find object of type "%s" with id %s.', $mapping->id, $id));
            }
        }
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        if ($mapping->multiple) {
            $containerEntityClass = $mapping->fileClass;
            $fileContainer = new $containerEntityClass();
            $files = $propertyAccessor->getValue($subject, $mapping->fileCollectionProperty);
            if ($files instanceof \Traversable) {
                $files = iterator_to_array($files);
            }
            $files[] = $fileContainer;
            $propertyAccessor->setValue($subject, $mapping->fileCollectionProperty, $files);
            $subject = $fileContainer;
        }
        $propertyAccessor->setValue($subject, $mapping->fileProperty, $uploadedFile);
        $handler = $this->getHandler($mapping, $subject);
        $handler->upload($subject, $mapping->fileProperty, $uploadedFile);

        $this->em->persist($subject);
        $this->em->flush();

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
        $obj->setCreatedAt(new \DateTime());

        $this->em->persist($obj);
        $this->em->flush($obj);

        return $obj;
    }

    /**
     * @param TemporaryUploadedFileInterface $fileInfo
     */
    public function removeTemporary(TemporaryUploadedFileInterface $fileInfo)
    {
        $this->tmpStorage->remove($fileInfo->getKey());
        $this->em->remove($fileInfo);
        $this->em->flush($fileInfo);
    }

    /**
     * @param Mapping $mapping
     * @param int     $id
     * @param bool    $deleteObject
     *
     * @throws \Exception
     */
    public function remove($mapping, $id, $deleteObject = false)
    {
        if (null !== $id) {
            $subject = $this->em->getRepository($mapping->fileClass)->find($id);
            if (null === $subject) {
                throw new \Exception(sprintf('Cannot find object of type "%s" with id %s.', $mapping->id, $id));
            }
            $handler = $this->getHandler($mapping, $subject);
            $field = $mapping->fileProperty;
            $handler->remove($subject, $field);
            if ($deleteObject || $mapping->multiple) {
                $this->em->remove($subject);
            }
            $this->em->flush();
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
