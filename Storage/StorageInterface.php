<?php

namespace Sherlockode\AdvancedFormBundle\Storage;

interface StorageInterface
{
    /**
     * @param string $key
     *
     * @return string
     */
    public function read($key);

    /**
     * @param string  $key
     * @param string  $data
     *
     * @return bool
     */
    public function write($key, $data);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function remove($key);
}
