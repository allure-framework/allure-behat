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
     * @param array $config Extension configuration hash (from behat.yml)
     * @param ContainerBuilder $container ContainerBuilder instance
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $container->setParameter(
            'behat.formatter.classes',
            array('allure' => 'Allure\Behat\Formatter\AllureFormatter')
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
            ->useAttributeAsKey('name')
            ->prototype('variable');
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