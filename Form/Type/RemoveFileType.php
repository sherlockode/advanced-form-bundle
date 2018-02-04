<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RemoveFileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'mapping',
                TextType::class
            )
            ->add(
                'id',
                IntegerType::class
            )
            ->add(
                'remove',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
        ;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'afb_remove_file';
    }
}
