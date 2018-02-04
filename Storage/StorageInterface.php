<?php

namespace Sherlockode\AdvancedFormBundle\Storage;

use Symfony\Component\HttpFoundation\File\File;

interface StorageInterface
{
    /**
     * @param $key
     *
     * @return File
     */
    public function read($key);

    /**
     * @param File $file
     *
     * @return File
     */
    public function write(File $file);

    /**
     * @param $key
     */
    public function remove($key);
}
