<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DependentEntityType
 */
class DependentEntityType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * DependentEntityType constructor.
     *
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     */
    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    public function getParent()
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('dependOnElementName')
            ->setDefaults([
                'ajax_url' => null,
                'mapping' => null,
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $depend = $this->getDependentElement($view, $options['dependOnElementName']);

        $mapping = $this->processMapping($options, $form);

        $class = isset($view->vars['attr']['class']) ? $view->vars['attr']['class'] : '';
        $class = $class . ' ' . 'dependent-entity';
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'class' => $class,
            'data-depend-on-element' => $depend->vars['id'],
            'data-mapping' => json_encode($mapping),
            'data-dependent-ajax-url' => $options['ajax_url'],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $data = $event->getData();
            if (!$data) {
                return;
            }

            $form = $event->getForm();
            if (!$this->hasValidValue($form, $data, $options)) {
                $form->addError(new FormError($this->translator->trans(
                    'dependent_entity.invalid_value',
                    [],
                    'AdvancedFormBundle'
                )));
            }
        });
    }

    private function processMapping(array $options, FormInterface $form)
    {
        if ($options['mapping'] === null) {
            return [];
        }

        if (is_array($options['mapping'])) {
            $mapping = $options['mapping'];
        } elseif (is_callable($options['mapping'])) {
            $dependForm = $this->getDependentForm($form, $options['dependOnElementName']);
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

        return $mapping;
    }


    /**
     * @param FormView $view
     * @param string   $dependantElementName
     *
     * @return FormView
     *
     * @throws \RuntimeException
     */
    private function getDependentElement(FormView $view, $dependantElementName)
    {
        if (isset($view->children[$dependantElementName])) {
            return $view->children[$dependantElementName];
        }

        if ($view->parent !== null) {
            return $this->getDependentElement($view->parent, $dependantElementName);
        }

        throw new \RuntimeException(
            sprintf('The %s must be defined after the form type it depends on.', __CLASS__)
        );
    }

    /**
     * @param FormInterface $form
     * @param string        $dependantElementName
     *
     * @return FormInterface
     *
     * @throws \RuntimeException
     */
    private function getDependentForm(FormInterface $form, $dependantElementName)
    {
        if ($form->has($dependantElementName)) {
            return $form->get($dependantElementName);
        }

        if ($form->getParent() !== null) {
            return $this->getDependentForm($form->getParent(), $dependantElementName);
        }

        throw new \RuntimeException(
            sprintf('Could not find "%s" element in form.', $dependantElementName)
        );
    }

    /**
     * @param FormInterface $form
     * @param string        $dependantElementName
     *
     * @return mixed
     */
    private function getDependentValue(FormInterface $form, $dependantElementName)
    {
        return $this->getDependentForm($form, $dependantElementName)->getData();
    }

    /**
     * @param FormInterface $form
     * @param int           $value
     * @param array         $options
     *
     * @return bool
     */
    private function hasValidValue(FormInterface $form, int $value, array $options): bool
    {
        if (null === $options['mapping']) {
            // if no mapping is provided, we cannot check the data
            return true;
        }

        $dependOnElementName = $options['dependOnElementName'];
        $mapping = $this->processMapping($options, $form);
        $dependOnValue = $this->getDependentValue($form, $dependOnElementName);

        if (is_object($dependOnValue) && isset($mapping[$dependOnValue->getId()])) {
            foreach ($mapping[$dependOnValue->getId()] as $row) {
                if ($value === $row['id']) {
                    return true;
                }
            }
        }

        return false;
    }
}
