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

    public function all()
    {
        $files = [];

        if ($this->dir) {
            foreach (new \DirectoryIterator($this->dir) as $fileInfo) {
                if (!$fileInfo->isDot()) {
                    $files[] = $fileInfo->getFilename();
                }
            }
        }

        return $files;
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

    public function getFileObject($key)
    {
        $path = sprintf('%s/%s', $this->dir, $key);

        if (!file_exists($path)) {
            return null;
        }

        return new File($path);
    }
}
