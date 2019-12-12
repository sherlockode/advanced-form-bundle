<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
        $builder
            ->add('file', FileType::class)
            ->add('mapping', TextType::class)
            ->add('id', IntegerType::class, [
                'required' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if (isset($data['mapping'])) {
                $mapping = $this->mappingManager->getMapping($data['mapping']);

                if ($mapping && $mapping->constraints) {
                    $form = $event->getForm();
                    $form->remove('file', FileType::class);
                    $form->add('file', FileType::class, [
                        'constraints' => array_map(function ($class) {
                            return new $class;
                        }, $mapping->constraints)
                    ]);
                }
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event){
            $form = $event->getForm();
            $mapping = $this->mappingManager->getMapping($form->get('mapping')->getData());
            $intMaxSize = $mapping->intMaxSize;

            if ($intMaxSize === null) {
                return;
            }

            if ($form->get('file')->getData()->getSize() > $intMaxSize) {
                $form->addError(new FormError(
                    $this->translator->trans('upload.error_max_size', [
                        '%maxSize%' => $mapping->maxSize
                    ], 'AdvancedFormBundle')
                ));
            }
        });
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'afb_upload_file';
    }
}
