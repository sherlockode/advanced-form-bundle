<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Sherlockode\AdvancedFormBundle\DependentEntity\DependentMapperPool;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
     * @var DependentMapperPool
     */
    private $mapperPool;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * DependentEntityType constructor.
     *
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     * @param DependentMapperPool    $mapperPool
     * @param UrlGeneratorInterface  $router
     */
    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        DependentMapperPool $mapperPool,
        UrlGeneratorInterface $router
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->mapperPool = $mapperPool;
        $this->router = $router;
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
        $depend = $this->getDependentElement($view, $options['dependOnElementName']);

        $mapping = $this->processMapping($options, $form);

        $class = isset($view->vars['attr']['class']) ? $view->vars['attr']['class'] : '';
        $class = $class . ' ' . 'dependent-entity';

        $ajaxUrl = $options['ajax_url'];
        if ($ajaxUrl === true) {
            if (!is_string($options['mapping'])) {
                throw new \Exception(
                    'In order to use ajax for dependent dropdown, '
                    .'you need to use a mapper or provide the URL explicitly in ajax_url'
                );
            }
            $ajaxUrl = $this->router->generate('sherlockode_afb_dependent_results', ['mapper' => $options['mapping']]);
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'class' => $class,
            'data-depend-on-element' => $depend->vars['id'],
            'data-mapping' => json_encode($mapping),
            'data-dependent-ajax-url' => $ajaxUrl,
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
        if ($options['mapping'] === null || is_string($options['ajax_url'])) {
            return [];
        }

        $dependForm = $this->getDependentForm($form, $options['dependOnElementName']);
        $mapping = [];
        if (is_string($options['mapping'])) {
            $mapper = $this->mapperPool->getMapper($options['mapping']);
            foreach ($dependForm->getConfig()->getAttribute('choice_list')->getChoices() as $choice) {
                list($k, $v) = $mapper->getMapping($choice);
                $mapping[$k] = $v;
            }
        } elseif (is_callable($options['mapping'])) {
            $mapping = [];
            foreach ($dependForm->getConfig()->getAttribute('choice_list')->getChoices() as $choice) {
                list($k, $v) = $options['mapping']($choice);
                $mapping[$k] = $v;
            }
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The "%s" option only supports string or callable, %s received.',
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
