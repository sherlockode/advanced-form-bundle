<?php

namespace Sherlockode\AdvancedFormBundle\UploadHandler;

use Sherlockode\AdvancedFormBundle\Storage\StorageInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PropertyUploadHandler implements UploadHandlerInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    private $propertyAccessor;

    public function __construct(StorageInterface $storage)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->storage = $storage;
    }

    public function upload($subject, $attribute, $file)
    {
        $newFile = $this->storage->write($file);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($subject, $attribute, $newFile->getBasename());
    }

    public function remove($subject, $attribute)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($subject, $attribute, null);
    }

    public function supports($subject, $attribute)
    {
        return $this->propertyAccessor->isWritable($subject, $attribute);
    }
}
