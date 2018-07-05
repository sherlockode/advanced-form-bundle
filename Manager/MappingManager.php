<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

class MappingManager
{
    /**
     * @var array
     */
    private $mapping;

    /**
     * @param array $mapping
     *
     * @return $this
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getMappedEntity($type)
    {
        if (isset($this->mapping[$type]) && isset($this->mapping[$type]['class'])) {
            return $this->mapping[$type]['class'];
        }

        return null;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getFileProperty($type)
    {
        if (isset($this->mapping[$type]) && isset($this->mapping[$type]['file_property'])) {
            return $this->mapping[$type]['file_property'];
        }

        return null;
    }

    public function getRouteProperty($type)
    {
        if (isset($this->mapping[$type]) && isset($this->mapping[$type]['route'])) {
            return $this->mapping[$type]['route'];
        }

        return null;
    }
}
