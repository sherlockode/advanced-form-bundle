<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Sherlockode\AdvancedFormBundle\Manager\Mapping;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadFileType extends AbstractType
{
    /**
     * @var MappingManager
     */
    private $mappingManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param MappingManager $mappingManager
     * @param TranslatorInterface $translator
     */
    public function __construct($mappingManager, $translator)
    {
        $this->mappingManager = $mappingManager;
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Mapping $mapping */
        $mapping = $options['mapping'];

        $builder
            ->add($mapping->fileProperty, FileType::class, [
                'mapped' => !$mapping->multiple,
                'constraints' => array_map(function ($class) {
                    return new $class;
                }, $mapping->constraints ?? [])

            ])
        ;

        if ($mapping->multiple) {
            $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($mapping) {
                // $data is the main entity
                $data = $event->getData();
                $form = $event->getForm();

                $propertyAccessor = PropertyAccess::createPropertyAccessor();

                // create file container object
                $uploadedFile = $form->get($mapping->fileProperty)->getData();
                $containerEntityClass = $mapping->fileClass;
                $fileContainer = new $containerEntityClass();
                $propertyAccessor->setValue($fileContainer, $mapping->fileProperty, $uploadedFile);

                // update the file collection
                $files = $propertyAccessor->getValue($data, $mapping->fileCollectionProperty);
                if ($files instanceof \Traversable) {
                    $files = iterator_to_array($files);
                }
                $files[] = $fileContainer;
                $propertyAccessor->setValue($data, $mapping->fileCollectionProperty, $files);

                return $data;
            });
        }

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($mapping) {
            $form = $event->getForm();
            $intMaxSize = $mapping->intMaxSize;

            if ($intMaxSize === null) {
                return;
            }

            if ($form->get($mapping->fileProperty)->getData()->getSize() > $intMaxSize) {
                $form->addError(new FormError(
                    $this->translator->trans('upload.error_max_size', [
                        '%maxSize%' => $mapping->maxSize
                    ], 'AdvancedFormBundle')
                ));
            }
        });
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired(['mapping']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'afb_upload_file';
    }
}
