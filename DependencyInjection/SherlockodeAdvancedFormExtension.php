<?php

namespace Sherlockode\AdvancedFormBundle\DependencyInjection;

use Sherlockode\AdvancedFormBundle\Storage\FilesystemStorage;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SherlockodeAdvancedFormExtension extends Extension
{
    /**
     * Loads the extension.
     *
     * @param array            $configs   The configuration
     * @param ContainerBuilder $container The container builder
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sherlockode_afb.uploader_mappings', $config['uploader_mappings']);

        $this->loadServices($container);
        $this->registerFormTheme($container);

        $storages = [];
        foreach ($config['storages'] as $name => $storage) {
            $definition = $this->getStorageDefinition($name, $storage);
            $storages[$name] = $definition;
            $container->setDefinition(sprintf('sherlockode_afb.%s_storage', $name), $definition);
        }
        $definition = $container->getDefinition('sherlockode_afb.upload_handler.property');
        $definition->setArgument(1, $storages);
    }

    /**
     * @param string $name
     * @param array  $config
     *
     * @return Definition
     */
    private function getStorageDefinition($name, $config)
    {
        if (isset($config['filesystem'])) {
            return new Definition(FilesystemStorage::class, [$config['filesystem']['path']]);
        }
        throw new \LogicException(sprintf('The storage %s is not correctly defined', $name));
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadServices(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $toBeLoaded = [
            'controller.yml',
            'form.yml',
            'manager.yml',
        ];
        foreach ($toBeLoaded as $file) {
            $loader->load($file);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerFormTheme(ContainerBuilder $container)
    {
        $resources = $container->hasParameter('twig.form.resources') ?
            $container->getParameter('twig.form.resources') : [];

        array_unshift($resources, '@SherlockodeAdvancedForm/Form/file.html.twig');
        $container->setParameter('twig.form.resources', $resources);
    }
}
