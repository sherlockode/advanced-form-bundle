<?php

namespace Sherlockode\AdvancedFormBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ALLOWED_SIZE_UNITS = [
        'k' => 1000,
        'ki' => 1 << 10,
        'm' => 1000 * 1000,
        'mi' => 1 << 20,
        'g' => 1000 * 1000 * 1000,
        'gi' => 1 << 30,
    ];

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tb = new TreeBuilder('sherlockode_advanced_form');
        $root = $tb->getRootNode();

        $this->addStorageSection($root);
        $this->addUploaderSection($root);

        $root
            ->children()
                ->scalarNode('tmp_uploaded_file_class')->defaultNull()->end()
                ->scalarNode('tmp_uploaded_file_dir')->defaultNull()->end()
            ->end()
        ;

        return $tb;
    }

    private function addStorageSection(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('storage')
            ->children()
                ->arrayNode('storages')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->arrayNode('filesystem')
                            ->children()
                                ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addUploaderSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('uploader_mappings')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                        ->booleanNode('multiple')->defaultFalse()->end()
                        ->arrayNode('constraints')
                            ->beforeNormalization()->castToArray()->end()
                            ->validate()
                                ->ifTrue(function ($constraints) {
                                    foreach ($constraints as $constraint) {
                                        if (!class_exists($constraint)) {
                                            return true;
                                        }
                                    }

                                    return false;
                                })
                                ->thenInvalid('Constraints class does not exists.')
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('file_property')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('file_collection_property')->defaultNull()->cannotBeEmpty()->end()
                        ->scalarNode('file_class')->cannotBeEmpty()->end()
                        ->scalarNode('handler')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('storage')->cannotBeEmpty()->end()
                        ->scalarNode('max_size')
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(function ($v) {
                                    if ($v === null) {
                                        return false;
                                    }
                                    if (ctype_digit((string) $v)) {
                                        return false;
                                    }
                                    if (preg_match('/^(\d++)('.implode('|', array_keys(self::ALLOWED_SIZE_UNITS)).')$/i', $v, $matches)) {
                                        return false;
                                    }

                                    return true;
                                })
                                ->thenInvalid('Allowed units are "K", "KI", "M", "MI", "G", "GI"')
                            ->end()
                        ->end()
                        ->arrayNode('route')
                            ->children()
                                ->scalarNode('name')->cannotBeEmpty()->end()
                                ->variableNode('parameters')->defaultValue([])->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
