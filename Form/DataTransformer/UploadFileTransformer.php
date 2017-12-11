<?php

namespace Sherlockode\AdvancedFormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileTransformer implements DataTransformerInterface
{
    /**
     * @var bool
     */
    private $isMultiple;

    /**
     * @param bool $isMultiple
     */
    public function __construct($isMultiple)
    {
        $this->isMultiple = (bool) $isMultiple;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($object)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        if (!$data) {
            return null;
        }

        if (is_array($data) && count($data) > 0) {
            if ($this->isMultiple) {
                $result = [];
                foreach ($data as $d) {
                    if (!empty($d['pathname']) && !empty($d['mime-type']) && !empty($d['size'])) {
                        $result[] = new UploadedFile(
                            $d['pathname'],
                            basename($d['pathname']),
                            $d['mime-type'],
                            $d['size'],
                            null,
                            true
                        );
                    }
                }

                return $result;
            }

            if (!empty($data['pathname']) && !empty($data['mime-type']) && !empty($data['size'])) {
                return new UploadedFile(
                    $data['pathname'],
                    basename($data['pathname']),
                    $data['mime-type'],
                    $data['size'],
                    null,
                    true
                );
            }

            throw new TransformationFailedException();
        }

        return null;
    }
}
