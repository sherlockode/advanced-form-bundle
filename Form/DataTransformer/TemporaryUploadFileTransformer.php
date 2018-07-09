<?php

namespace Sherlockode\AdvancedFormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TemporaryUploadFileTransformer implements DataTransformerInterface
{
    /**
     * @var bool
     */
    private $isMultiple;

    /**
     * @var string
     */
    private $path;

    /**
     * @param bool   $isMultiple
     * @param string $path
     */
    public function __construct($isMultiple, $path)
    {
        $this->isMultiple = (bool) $isMultiple;
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($object)
    {
        if ($object === null) {
            return [];
        }
        return [];
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
                            $this->path . '/' . $data['key'],
                            'myfile',
                            null,
                            null,
                            null,
                            true
                        );
                    }
                }

                return $result;
            }

            if (!empty($data['key'])) {
                return new UploadedFile(
                    $this->path . '/' . $data['key'],
                    'myfile',
                    null,
                    null,
                    null,
                    true
                );
            }
        }

        return null;
    }
}
