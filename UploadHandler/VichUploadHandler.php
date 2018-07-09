<?php

namespace Sherlockode\AdvancedFormBundle\UploadHandler;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;

class VichUploadHandler implements UploadHandlerInterface
{
    /**
     * @var UploadHandler
     */
    private $uploadHandler;

    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(UploadHandler $uploadHandler, Reader $annotationReader)
    {
        $this->uploadHandler = $uploadHandler;
        $this->annotationReader = $annotationReader;
    }

    public function upload($subject, $attribute, File $file)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($subject, $attribute, $file);
        $this->uploadHandler->upload($subject, $attribute);
    }

    public function remove($subject, $attribute)
    {
        $this->uploadHandler->remove($subject, $attribute);
    }

    public function supports($subject, $attribute)
    {
        $property = new \ReflectionProperty(get_class($subject), $attribute);
        $annotation = $this->annotationReader->getPropertyAnnotation($property, UploadableField::class);

        return $annotation !== null;
    }
}
