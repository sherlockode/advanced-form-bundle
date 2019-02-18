<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Sherlockode\AdvancedFormBundle\Form\DataTransformer\TemporaryUploadFileTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemporaryUploadedFileType extends AbstractType
{
    /**
     * @var string
     */
    private $tmpFileClass;

    /**
     * @var ObjectManager
     */
    private $om;

    public function __construct(ObjectManager $om, $tmpFileClass)
    {
        $this->om = $om;
        $this->tmpFileClass = $tmpFileClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('key', TextType::class)
            ->add('token', TextType::class)
        ;

        $builder->addViewTransformer(new TemporaryUploadFileTransformer($options['temporary_path']));
        $builder->addViewTransformer(new CallbackTransformer(function ($data) {
            // norm to view is not needed
            return null;
        }, function ($data) {
            if (!is_array($data)) {
                return null;
            }
            $tmpFile = $this->om->getRepository($this->tmpFileClass)->findOneBy($data);
            return $tmpFile;
        }));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'temporary_path' => sys_get_temp_dir(),
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'afb_temporary_uploaded_file';
    }
}
