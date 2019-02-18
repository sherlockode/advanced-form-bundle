<?php

namespace Sherlockode\AdvancedFormBundle\Form\DataTransformer;

use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class TemporaryUploadFileTransformer
 *
 * Transforms a TemporaryUploadedFileInterface into a simulated UploadedFile instance
 */
class TemporaryUploadFileTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
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
        if (!$data || !$data instanceof TemporaryUploadedFileInterface) {
            return null;
        }

        return new UploadedFile(
            $this->path . '/' . $data->getKey(),
            $data->getFilename(),
            null,
            null,
            null,
            true
        );
    }
}
