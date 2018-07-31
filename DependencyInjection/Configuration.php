<?php

namespace Sherlockode\AdvancedFormBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $root = $tb->root('sherlockode_advanced_form');

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
                        ->scalarNode('file_property')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('file_collection_property')->defaultNull()->cannotBeEmpty()->end()
                        ->scalarNode('file_class')->cannotBeEmpty()->end()
                        ->scalarNode('handler')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('storage')->cannotBeEmpty()->end()
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
