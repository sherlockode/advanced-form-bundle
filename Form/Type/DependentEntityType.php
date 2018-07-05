<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DependentEntityType
 */
class DependentEntityType extends AbstractType
{
    public function getParent()
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('dependOnElementName')
            ->setRequired('mapping')
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        if (!isset($view->parent->children[$options['dependOnElementName']])) {
            throw new \RuntimeException(
                sprintf('The %s must be defined after the form type it depends on.', __CLASS__)
            );
        }

        if (is_array($options['mapping'])) {
            $mapping = $options['mapping'];
        } elseif (is_callable($options['mapping'])) {
            $dependForm = $form->getParent()->get($options['dependOnElementName']);
            $mapping = [];
            foreach ($dependForm->getConfig()->getAttribute('choice_list')->getChoices() as $choice) {
                list($k, $v) = $options['mapping']($choice);
                $mapping[$k] = $v;
            }
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The "%s" option only supports array or callable, %s received.',
                    'mapping',
                    gettype($options['mapping'])
                )
            );
        }

        $class = isset($view->vars['attr']['class']) ? $view->vars['attr']['class'] : '';
        $class = $class . ' ' . 'dependent-entity';
        $depend = $view->parent->children[$options['dependOnElementName']];
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'class' => $class,
            'data-depend-on-element' => $depend->vars['id'],
            'data-mapping' => json_encode($mapping),
        ]);
    }
}
