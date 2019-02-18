<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Sherlockode\AdvancedFormBundle\Event\UploadEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class TemporaryFileCollectionType extends AbstractType
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new CallbackTransformer(function ($data) {
            // norm to view is not needed
            return [];
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
                $this->eventDispatcher->dispatch('afb.post_upload', new UploadEvent($fileContainer, $mapping, $uploadedFile));
                $files[] = $fileContainer;
            }

            return $files;
        }));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => TemporaryUploadedFileType::class,
            'allow_add' => true,
            'allow_delete' => false,
        ]);

        $resolver->setRequired('mapping');
    }

    public function getParent()
    {
        return CollectionType::class;
    }
}
