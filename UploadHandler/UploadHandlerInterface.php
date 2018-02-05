<?php

namespace Sherlockode\AdvancedFormBundle\UploadHandler;

/**
 * Interface UploadHandlerInterface
 */
interface UploadHandlerInterface
{
    public function supports($subject, $attribute);

    public function upload($subject, $attribute, $file);

    public function remove($subject, $attribute);
}
