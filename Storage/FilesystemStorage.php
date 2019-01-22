<?php

namespace Sherlockode\AdvancedFormBundle\Storage;

class FilesystemStorage implements StorageInterface
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function write($key, $data)
    {
        return file_put_contents($this->dir . '/' . $key, $data);
    }

    public function read($key)
    {
        return file_get_contents($this->dir . '/' . $key);
    }

    public function remove($key)
    {
        return unlink($this->dir  .'/' . $key);
    }
}
