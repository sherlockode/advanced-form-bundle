<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UploadedFileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'pathname',
                TextType::class
            )
            ->add(
                'mime-type',
                TextType::class
            )
            ->add(
                'size',
                IntegerType::class
            )
        ;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'afb_uploaded_file';
    }
}
