<?php

namespace Sherlockode\AdvancedFormBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EntityMappingType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var MappingManager
     */
    private $mappingManager;

    /**
     * @param EntityManagerInterface $em
     * @param MappingManager         $mappingManager
     */
    public function __construct(EntityManagerInterface $em, MappingManager $mappingManager)
    {
        $this->em = $em;
        $this->mappingManager = $mappingManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mapping', TextType::class)
            ->add('entity', IntegerType::class, [
                'required' => false,
            ])
            ->add('fileEntity', IntegerType::class, [
                'required' => false,
            ])
        ;

        $builder->addViewTransformer(new CallbackTransformer(function ($data) {
            return [
                'mapping' => isset($data['mapping']) ? $data['mapping']->id : null,
                'entity'  => isset($data['entity']) ? $data['entity']->getId() : null,
                'fileEntity'  => isset($data['fileEntity']) ? $data['fileEntity']->getId() : null,
            ];
        }, function ($data) {
            $mapping = $this->mappingManager->getMapping($data['mapping']);

            $entity = null;
            $fileEntity = null;

            if (isset($data['entity'])) {
                $entity = $this->em->getRepository($mapping->class)->find($data['entity']);
                if (null === $entity) {
                    throw new TransformationFailedException(
                        sprintf('Cannot find object of type "%s" with id %s.', $mapping->id, $data['entity'])
                    );
                }
            }
            if (isset($data['fileEntity'])) {
                $fileEntity = $this->em->getRepository($mapping->fileClass)->find($data['fileEntity']);
                if (null === $fileEntity) {
                    throw new TransformationFailedException(
                        sprintf('Cannot find object of type "%s" with id %s.', $mapping->id, $data['entity'])
                    );
                }
            }

            return ['mapping' => $mapping, 'entity' => $entity, 'fileEntity' => $fileEntity];
        }));
    }
}
