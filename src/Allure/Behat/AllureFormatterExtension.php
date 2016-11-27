<?php
/**
 * Copyright (c) Eduard Sukharev
 * Apache 2.0 License. See LICENSE.md for full license text.
 */

namespace Allure\Behat;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Behat\Behat\Extension\ExtensionInterface;

class AllureFormatterExtension implements ExtensionInterface
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $config    Extension configuration hash (from behat.yml)
     * @param ContainerBuilder $container ContainerBuilder instance
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $container->setParameter(
            'behat.formatter.classes',
            array('allure' => 'Allure\Behat\Adapter\AllureFormatter')
        );
    }
    /**
     * Setups configuration for current extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function getConfig(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('output')->defaultValue('build' . DIRECTORY_SEPARATOR . 'allure-results')->end()
                ->arrayNode('ignored_annotations')
                ->end()
                ->booleanNode('delete_previous_results')->defaultValue(true)->end()
            ->end()
        ;
    }
    /**
     * Returns compiler passes used by this extension.
     *
     * @return array
     */
    public function getCompilerPasses()
    {
        return array();
    }
}