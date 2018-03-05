<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Sherlockode\AdvancedFormBundle\Form\DataTransformer\UploadFileTransformer;
use Sherlockode\AdvancedFormBundle\Manager\AnnotationManager;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class FileType
 */
class FileType extends AbstractType
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var AnnotationManager
     */
    private $annotationManager;

    /**
     * @var MappingManager
     */
    private $mappingManager;

    /**
     * @required
     *
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return $this
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;

        return $this;
    }

    /**
     * @required
     *
     * @param AnnotationManager $annotationManager
     *
     * @return $this
     */
    public function setAnnotationManager(AnnotationManager $annotationManager)
    {
        $this->annotationManager = $annotationManager;

        return $this;
    }

    /**
     * @required
     *
     * @param MappingManager $mappingManager
     *
     * @return $this
     */
    public function setMappingManager(MappingManager $mappingManager)
    {
        $this->mappingManager = $mappingManager;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['mapping']);
        $resolver->setDefaults([
            'upload_uri_path' => $this->urlGenerator->generate('sherlockode_afb_upload'),
            'remove_uri_path' => $this->urlGenerator->generate('sherlockode_afb_remove'),
            'multiple' => false,
            'js_callback' => null,
            'mapped' => false,
            'compound' => true,
            'image_preview' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $subject = $form->getParent()->getData();
        $entityNamespace = $this->mappingManager->getMappedEntity($options['mapping']);
        $fileProperty = $this->mappingManager->getFileProperty($options['mapping']);
        $vichAnnotation = $this->annotationManager->getVichAnnotations($entityNamespace, $fileProperty);
        if ($vichAnnotation) {
            $fileNameProperty = $vichAnnotation->getFileNameProperty();
        } else {
            $fileNameProperty = $fileProperty;
            $fileProperty = null;
        }

        $view->vars['uploadUriPath'] = $options['upload_uri_path'];
        $view->vars['removeUriPath'] = $options['remove_uri_path'];
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['jsCallback'] = $options['js_callback'];
        $view->vars['fieldFile'] = $fileProperty;
        $view->vars['fieldName'] = $fileNameProperty;
        $view->vars['subject'] = $subject;
        $view->vars['mapping'] = $options['mapping'];
        $view->vars['imagePreview'] = $options['image_preview'];
        $view->vars['files'] = [];

        if ($options['multiple']) {
            $collection = $propertyAccessor->getValue($subject, $form->getName());
            if ($collection instanceof Collection) {
                foreach ($collection as $media) {
                    $view->vars['files'][] = $media;
                }
            }
        } else {
            if ($subject->getId()) {
                $imageName = $propertyAccessor->getValue($subject, $fileNameProperty);
                if (!empty($imageName)) {
                    $view->vars['files'][] = $subject;
                }
            }
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isMultiple = (bool) $options['multiple'];
        $entityNamespace = $this->mappingManager->getMappedEntity($options['mapping']);
        $fileProperty = $this->mappingManager->getFileProperty($options['mapping']);

        if ($isMultiple) {
            $builder->add(
                'files',
                CollectionType::class,
                [
                    'entry_type' => UploadedFileType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                ]
            );
        } else {
            $builder->add(
                'files',
                UploadedFileType::class
            );
        }

        $builder->get('files')->addModelTransformer(new UploadFileTransformer($isMultiple));
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($isMultiple, $entityNamespace, $fileProperty) {
                $data = $event->getData();

                if (null === $data) {
                    return;
                }

                $form = $event->getForm();
                $object = $form->getParent()->getData();
                $propertyAccessor = PropertyAccess::createPropertyAccessor();

                if ($isMultiple) {
                    if (is_array($data['files']) && count($data['files']) > 0) {
                        $collection = $propertyAccessor->getValue($object, $form->getName());
                        foreach ($data['files'] as $uploadedFile) {
                            $item = new $entityNamespace;
                            $propertyAccessor->setValue($item, $fileProperty, $uploadedFile);
                            $collection->add($item);
                        }
                        $propertyAccessor->setValue($object, $form->getName(), $collection);
                    }
                } else {
                    if (isset($data['files'])) {
                        $propertyAccessor->setValue($object, $form->getName(), $data['files']);
                    }
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'afb_file';
    }
}
