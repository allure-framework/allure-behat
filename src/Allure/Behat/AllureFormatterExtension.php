<?php
/**
 * Copyright (c) 2016 Eduard Sukharev
 * Copyright (c) 2018 Tiko Lakin.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * See LICENSE.md for full license text.
 */
namespace Allure\Behat;

use Allure\Behat\Attachment\ScreenshotProvider;
use Allure\Behat\Attachment\TextAttachmentProvider;
use Allure\Behat\Formatter\AllureFormatter;
use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Behat\Testwork\Exception\ServiceContainer\ExceptionExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use EzSystems\EzPlatformAdminUi\Behat\Helper\TestLogProvider;

class AllureFormatterExtension implements ExtensionInterface
{
    private const SCREENSHOT_UPLOADER_SERVICE_ID = 'bex.screenshot_extension.screenshot_uploader';

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * Returns the extension config key.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return 'allure';
    }

    /**
     * Initializes other extensions.
     *
     * This method is called immediately after all extensions are activated but
     * before any extension `configure()` method is called. This allows extensions
     * to hook into the configuration of other extensions providing such an
     * extension point.
     *
     * @param ExtensionManager $extensionManager
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * Setups configuration for the extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->children()->scalarNode('name')->defaultValue('allure');
        $builder->children()->scalarNode('issue_tag_prefix')->defaultValue(null);
        $builder->children()->scalarNode('test_id_tag_prefix')->defaultValue(null);
        $builder->children()->scalarNode('ignored_tags')->defaultValue(null);
        $builder->children()->scalarNode('severity_key')->defaultValue(null);
        $builder->children()->scalarNode('image_attachment_limit')->defaultValue(10);
    }

    /**
     * Loads extension services into temporary container.
     *
     * @param ContainerBuilder $container
     * @param array $config
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadFormatter($container, $config);

        if (class_exists(TestLogProvider::class)) {
            $this->loadTestAttachmentProvider($container);
        }

        if ($container->has(self::SCREENSHOT_UPLOADER_SERVICE_ID)) {
            $this->loadScreenshotExtension($container, $config);
        }
    }

    private function loadFormatter(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition(AllureFormatter::class);
        $definition->addArgument($config['name']);
        $definition->addArgument($config['issue_tag_prefix']);
        $definition->addArgument($config['test_id_tag_prefix']);
        $definition->addArgument($config['ignored_tags']);
        $definition->addArgument($config['severity_key']);
        $definition->addArgument('%paths.base%');
        $definition->addArgument(new Reference(ExceptionExtension::PRESENTER_ID));
        $container->setDefinition('allure.formatter', $definition)->addTag('output.formatter');
    }

    private function loadTestAttachmentProvider(ContainerBuilder $containerBuilder)
    {
        $definition = new Definition(TextAttachmentProvider::class);
        $definition->addArgument(new Reference('mink'));
        $definition->addArgument(new Reference(Symfony2Extension::KERNEL_ID));
        $definition->addTag('event_dispatcher.subscriber');
        $containerBuilder->setDefinition(TextAttachmentProvider::class, $definition);
    }

    private function loadScreenshotExtension(ContainerBuilder $container, array $config)
    {
        $container
        ->register(ScreenshotProvider::class)
        ->setDecoratedService(self::SCREENSHOT_UPLOADER_SERVICE_ID)
        ->addArgument(new Reference('bex.screenshot_extension.indented_output'))
        ->addArgument(new Reference('bex.screenshot_extension.config'))
        ->addArgument($config['image_attachment_limit']);
    }
}
