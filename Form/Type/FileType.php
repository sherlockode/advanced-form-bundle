<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Sherlockode\AdvancedFormBundle\Form\DataTransformer\TemporaryUploadFileTransformer;
use Sherlockode\AdvancedFormBundle\Manager\AnnotationManager;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
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
     * @var string
     */
    private $temporaryPath;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param AnnotationManager     $annotationManager
     * @param MappingManager        $mappingManager
     * @param string|null           $temporaryPath
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, AnnotationManager $annotationManager, MappingManager $mappingManager, $temporaryPath = null)
    {
        $this->urlGenerator = $urlGenerator;
        $this->annotationManager = $annotationManager;
        $this->mappingManager = $mappingManager;
        $this->temporaryPath = $temporaryPath ?? sys_get_temp_dir();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['mapping', 'upload_mode']);
        $resolver->setDefaults([
            'upload_uri_path' => function (Options $options) {
                if ($options['upload_mode'] == 'temporary') {
                    $url = $this->urlGenerator->generate('sherlockode_afb_upload_tmp');
                } elseif ($options['upload_mode'] == 'immediate') {
                    $url = $this->urlGenerator->generate('sherlockode_afb_upload');
                } else {
                    $url = null;
                }

                return $url;
            },
            'remove_uri_path' => $this->urlGenerator->generate('sherlockode_afb_remove'),
            'remove_tmp_uri_path' => $url = $this->urlGenerator->generate('sherlockode_afb_remove_tmp'),
            'multiple' => false,
            'js_callback' => null,
            'mapped' => function (Options $options) {
                return $options['upload_mode'] != 'immediate';
            },
            'compound' => true,
            'image_preview' => false,
        ]);
        $resolver->setAllowedValues('upload_mode', ['immediate', 'temporary']);
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
        }

        $view->vars['uploadUriPath'] = $options['upload_uri_path'];
        $view->vars['removeUriPath'] = $options['remove_uri_path'];
        $view->vars['removeTmpUriPath'] = $options['remove_tmp_uri_path'];
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['jsCallback'] = $options['js_callback'];
        $view->vars['fieldName'] = $fileNameProperty;
        $view->vars['subject'] = $subject;
        $view->vars['mapping'] = $options['mapping'];
        $view->vars['imagePreview'] = $options['image_preview'];
        $view->vars['uploadMode'] = $options['upload_mode'];
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
        if ($options['upload_mode'] == 'immediate') {
            return;
        }

        $isMultiple = (bool) $options['multiple'];

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
            $builder->addViewTransformer(new CallbackTransformer(function ($data) {
                return ['files' => $data];
            }, function ($data) {
                return $data['files'];
            }));
        }

        $builder->get('files')->addViewTransformer(new TemporaryUploadFileTransformer($isMultiple, $this->temporaryPath));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'afb_file';
    }
}
