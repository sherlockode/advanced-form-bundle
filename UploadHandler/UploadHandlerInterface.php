<?php

namespace Sherlockode\AdvancedFormBundle\UploadHandler;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Interface UploadHandlerInterface
 */
interface UploadHandlerInterface
{
    /**
     * @param object $subject
     * @param string $attribute
     *
     * @return bool
     */
    public function supports($subject, $attribute);

    /**
     * @param object $subject
     * @param string $attribute
     * @param File   $file
     */
    public function upload($subject, $attribute, File $file);

    /**
     * @param object $subject
     * @param string $attribute
     */
    public function remove($subject, $attribute);

    /**
     * @param object $subject
     * @param string $attribute
     *
     * @return string
     */
    public function getFilename($subject, $attribute);
}
