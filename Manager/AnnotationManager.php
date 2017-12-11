<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

use Doctrine\Common\Annotations\AnnotationReader;

class AnnotationManager
{
    /**
     * @param string $entity
     * @param string $property
     *
     * @return array|null
     */
    public function getVichAnnotations($entity, $property)
    {
        $annotationReader = new AnnotationReader();
        $reflectedClass = new \ReflectionClass($entity);
        foreach ($reflectedClass->getProperties() as $p) {
            if ($property == $p->getName()) {
                $annotations = $annotationReader->getPropertyAnnotations($p);
                return array_shift($annotations);
            }
        }

        return null;
    }
}
