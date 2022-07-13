<?php

namespace Sherlockode\AdvancedFormBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectRepository;
use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class TemporaryUploadFileTransformer
 *
 * ReverseTransforms a TemporaryUploadedFileInterface into a simulated UploadedFile instance
 * Usage : On POST action, the reverseTransform will simulate an upload from a file already uploaded on the server
 */
class TemporaryUploadFileTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var ObjectRepository
     */
    private $repository;

    /**
     * @param string           $path
     * @param ObjectRepository $repository
     */
    public function __construct($path, $repository)
    {
        $this->path = $path;
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($object)
    {
        if (!$object instanceof File) {
            return null;
        }

        $key = $object->getFilename();

        return $this->repository->findOneBy(['key' => $key]);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        if (!$data || !$data instanceof TemporaryUploadedFileInterface) {
            return null;
        }

        // generate an uploaded file from the TemporaryUploadedFileInterface (file was uploaded in previous request)
        return new UploadedFile(
            $this->path . '/' . $data->getKey(),
            $data->getFilename(),
            null,
            null,
            true
        );
    }
}
