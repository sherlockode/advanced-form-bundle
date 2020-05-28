<?php

namespace Sherlockode\AdvancedFormBundle\DependentEntity;

interface DependentMapperInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getSubjectClass();

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getMapping($entity);

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getDependentResults($entity);
}
