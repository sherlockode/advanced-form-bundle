<?php

namespace Sherlockode\AdvancedFormBundle\Storage;

use Symfony\Component\HttpFoundation\File\File;

interface StorageInterface
{
    /**
     * @return array
     */
    public function all();

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

    /**
     * @param string $key
     *
     * @return File
     */
    public function getFileObject($key);
}
