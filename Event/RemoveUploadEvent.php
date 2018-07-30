<?php

namespace Sherlockode\AdvancedFormBundle\Event;

use Sherlockode\AdvancedFormBundle\Manager\Mapping;
use Symfony\Component\EventDispatcher\Event;

class RemoveUploadEvent extends Event
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
     * UploadEvent constructor.
     *
     * @param              $subject
     * @param Mapping      $mapping
     */
    public function __construct($subject, Mapping $mapping)
    {
        $this->subject = $subject;
        $this->mapping = $mapping;
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
}
