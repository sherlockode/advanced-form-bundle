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

    /**
     * @param object $object
     * @param string $attribute
     *
     * @return string|null
     */
    public function getStorage($object, $attribute)
    {
        foreach ($this->mapping as $type => $data) {
            if ($data['class'] == get_class($object) && $data['file_property'] == $attribute) {
                return $data['storage'] ?? null;
            }
        }

        return null;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getHandlerCode($type)
    {
        if (isset($this->mapping[$type]) && isset($this->mapping[$type]['handler'])) {
            return $this->mapping[$type]['handler'];
        }

        return null;
    }
}
