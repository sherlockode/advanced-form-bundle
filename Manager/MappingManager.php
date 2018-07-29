<?php

namespace Sherlockode\AdvancedFormBundle\Manager;

class MappingManager
{
    /**
     * @var array
     */
    private $mapping;

    /**
     * @var Mapping[]
     */
    private $mappings;

    /**
     * @param array $mapping
     *
     * @return $this
     */
    public function setMappingData($mapping)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return Mapping
     * @throws \Exception
     */
    public function getMapping($type)
    {
        if (!isset($this->mappings[$type])) {
            if (!isset($this->mapping[$type])) {
                throw new \Exception(sprintf('Mapping "%s" does not exist', $type));
            }
            $data = $this->mapping[$type];

            $mapping = new Mapping();
            $mapping->id = $type;
            $mapping->class = $data['class'];
            $mapping->multiple = $data['multiple'];
            $mapping->fileClass = $data['multiple'] ? $data['file_class'] : $data['class'];
            $mapping->fileProperty = $data['file_property'];
            $mapping->fileCollectionProperty = $data['file_collection_property'];
            $mapping->handler = $data['handler'];
            $mapping->route = $data['route'] ?? null;

            $this->mappings[$type] = $mapping;
        }

        return $this->mappings[$type];
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
            $class = $data['multiple'] ? $data['file_class'] : $data['class'];
            if ($class == get_class($object) && $data['file_property'] == $attribute) {
                return $data['storage'] ?? null;
            }
        }

        return null;
    }
}
