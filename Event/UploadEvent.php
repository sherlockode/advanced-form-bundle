<?php

namespace Sherlockode\AdvancedFormBundle\Event;

use Sherlockode\AdvancedFormBundle\Manager\Mapping;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadEvent extends Event
{
    /**
     * @var object
     */
    private $subject;

    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @var UploadedFile
     */
    private $uploadedFile;

    /**
     * UploadEvent constructor.
     *
     * @param              $subject
     * @param Mapping      $mapping
     * @param UploadedFile $uploadedFile
     */
    public function __construct($subject, Mapping $mapping, UploadedFile $uploadedFile)
    {
        $this->subject = $subject;
        $this->mapping = $mapping;
        $this->uploadedFile = $uploadedFile;
    }

    /**
     * @return object
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return Mapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @return UploadedFile
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }
}
