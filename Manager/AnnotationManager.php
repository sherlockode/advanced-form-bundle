<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;

class AnnotationManager
{
    /**
     * @param string $entity
     * @param string $property
     *
     * @return UploadableField|null
     */
    public function getVichAnnotations($entity, $property)
    {
        $annotationReader = new AnnotationReader();
        $reflectedClass = new \ReflectionClass($entity);
        foreach ($reflectedClass->getProperties() as $p) {
            if ($property == $p->getName()) {
                return $annotationReader->getPropertyAnnotation($p, UploadableField::class);
            }
        }

        return null;
    }
}
