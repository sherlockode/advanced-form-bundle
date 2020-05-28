<?php

namespace Sherlockode\AdvancedFormBundle\DependentEntity;

class DependentMapperPool
{
    /**
     * @var DependentMapperInterface[]
     */
    private $mappers = [];

    public function addMapper(DependentMapperInterface $mapper)
    {
        $this->mappers[$mapper->getName()] = $mapper;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return DependentMapperInterface
     * @throws \Exception
     */
    public function getMapper($name)
    {
        if (!$this->hasMapper($name)) {
            throw new \Exception(sprintf('Unknown dependent entity mapper %s', $name));
        }

        return $this->mappers[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasMapper($name)
    {
        return isset($this->mappers[$name]);
    }
}
