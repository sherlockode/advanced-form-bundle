<?php

namespace Sherlockode\AdvancedFormBundle\Storage;

use Symfony\Component\HttpFoundation\File\File;

class FilesystemStorage implements StorageInterface
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function write(File $file)
    {
        $newName = sha1(microtime(true) . rand())  . '.' . $file->guessExtension();

        return $file->move($this->dir, $newName);
    }

    public function read($key)
    {
        return new File($this->dir . '/' . $key);
    }

    public function remove($key)
    {
        unlink($this->dir  .'/' . $key);
    }
}
