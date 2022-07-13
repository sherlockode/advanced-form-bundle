<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FileCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new CallbackTransformer(function ($data) use ($options) {
            if (null === $data) {
                return [];
            }
            $newData = [];
            $mapping = $options['mapping'];
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            foreach ($data as $item) {
                $uploadedFile = $propertyAccessor->getValue($item, $mapping->fileProperty);
                $newData[] = $uploadedFile;
            }

            return $newData;
        }, function ($data) use ($options) {
            // transform the collection of UploadedFile into an array of entities
            // holding the UploadedFile instances
            $files = [];
            $mapping = $options['mapping'];
            $fileClass = $mapping->fileClass;
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            foreach ($data as $uploadedFile) {
                $fileContainer = new $fileClass();
                $propertyAccessor->setValue($fileContainer, $mapping->fileProperty, $uploadedFile);
                $files[] = $fileContainer;
            }

            return $files;
        }));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => function (Options $options) {
                return $options['mode'] === 'temporary' ? TemporaryUploadedFileType::class : SymfonyFileType::class;
            },
            'allow_add' => true,
            'allow_delete' => false,
            'mode' => 'temporary',
        ]);

        $resolver->setRequired('mapping');
    }

    public function getParent(): ?string
    {
        return CollectionType::class;
    }
}
