<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
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
     * @var UploadManager
     */
    private $uploadManager;

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
     * @param UploadManager         $uploadManager
     * @param MappingManager        $mappingManager
     * @param string|null           $temporaryPath
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, UploadManager $uploadManager, MappingManager $mappingManager, $temporaryPath = null)
    {
        $this->urlGenerator = $urlGenerator;
        $this->uploadManager = $uploadManager;
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
            'js_callback' => null,
            'js_pre_remove_callback' => null,
            'js_error_callback' => null,
            'mapped' => function (Options $options) {
                return $options['upload_mode'] != 'immediate';
            },
            'compound' => true,
            'image_preview' => false,
            'dropzone_label' => null,
            'async' => true,
        ]);
        $resolver->setAllowedValues('upload_mode', ['immediate', 'temporary']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $subject = $form->getParent()->getData();
        $mapping = $this->mappingManager->getMapping($options['mapping']);
        $isMultiple = $mapping->multiple;

        $view->vars['uploadUriPath'] = $options['upload_uri_path'];
        $view->vars['removeUriPath'] = $options['remove_uri_path'];
        $view->vars['removeTmpUriPath'] = $options['remove_tmp_uri_path'];
        $view->vars['multiple'] = $isMultiple;
        $view->vars['async'] = $options['async'];
        $view->vars['jsCallback'] = $options['js_callback'];
        $view->vars['jsPreRemoveCallback'] = $options['js_pre_remove_callback'];
        $view->vars['jsErrorCallback'] = $options['js_error_callback'];
        $view->vars['subject'] = $subject;
        $view->vars['mapping'] = $options['mapping'];
        $view->vars['imagePreview'] = $options['image_preview'];
        $view->vars['uploadMode'] = $options['upload_mode'];
        $view->vars['files'] = [];
        $view->vars['dropzoneLabel'] = $options['dropzone_label'];

        if ($isMultiple) {
            if ($subject !== null) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();
                $collection = $propertyAccessor->getValue($subject, $form->getName());
                if ($collection instanceof Collection) {
                    foreach ($collection as $media) {
                        $view->vars['files'][] = $media;
                    }
                }
            }
        } else {
            if ($subject && $subject->getId()) {
                $fileName = $this->uploadManager->getFilename($mapping, $subject);
                if (!empty($fileName)) {
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

        $mapping = $this->mappingManager->getMapping($options['mapping']);
        $isMultiple = $mapping->multiple;

        if ($isMultiple) {
            $builder->add('files', FileCollectionType::class, [
                'entry_options' => ['temporary_path' => $this->temporaryPath],
                'mapping' => $mapping,
                'mode' => 'temporary',
            ]);

        } else {
            $builder->add('files', TemporaryUploadedFileType::class, [
                'temporary_path' => $this->temporaryPath,
            ]);
        }
        $builder->addViewTransformer(new CallbackTransformer(function ($data) {
            return ['files' => $data];
        }, function ($data) {
            return $data['files'];
        }));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'afb_file';
    }
}
