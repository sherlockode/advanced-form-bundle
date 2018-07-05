<?php

namespace Sherlockode\AdvancedFormBundle\UploadHandler;

use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Sherlockode\AdvancedFormBundle\Storage\StorageInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PropertyUploadHandler implements UploadHandlerInterface
{
    /**
     * @var StorageInterface[]
     */
    private $storages;

    private $propertyAccessor;

    /**
     * @var MappingManager
     */
    private $mappingManager;

    public function __construct(MappingManager $mappingManager, array $storages = [])
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->storages = $storages;
        $this->mappingManager = $mappingManager;
    }

    public function upload($subject, $attribute, $file)
    {
        $newFile = $this->getStorage($subject, $attribute)->write($file);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($subject, $attribute, $newFile->getBasename());
    }

    public function remove($subject, $attribute)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->getStorage($subject, $attribute)->remove($propertyAccessor->getValue($subject, $attribute));
        $propertyAccessor->setValue($subject, $attribute, null);
    }

    public function supports($subject, $attribute)
    {
        return $this->propertyAccessor->isWritable($subject, $attribute);
    }

    /**
     * @param object $subject
     * @param string $attribute
     *
     * @return StorageInterface
     * @throws \Exception
     */
    private function getStorage($subject, $attribute)
    {
        $storageName = $this->mappingManager->getStorage($subject, $attribute);

        if (!$storageName || !isset($this->storages[$storageName])) {
            throw new \Exception(
                sprintf('Storage not found for object of type %s and property %s', get_class($subject), $attribute)
            );
        }

        return $this->storages[$storageName];
    }
}
