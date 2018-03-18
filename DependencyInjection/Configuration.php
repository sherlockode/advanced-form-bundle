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

        $this->addUploaderSection($root);

        return $tb;
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
                        ->scalarNode('entity')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('file_property')->isRequired()->cannotBeEmpty()->end()
                        ->arrayNode('route')
                            ->children()
                                ->scalarNode('name')->end()
                                ->variableNode('parameters')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
