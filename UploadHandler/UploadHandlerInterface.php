<?php

namespace Sherlockode\AdvancedFormBundle\UploadHandler;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Interface UploadHandlerInterface
 */
interface UploadHandlerInterface
{
    public function supports($subject, $attribute);

    public function upload($subject, $attribute, File $file);

    public function remove($subject, $attribute);
}
