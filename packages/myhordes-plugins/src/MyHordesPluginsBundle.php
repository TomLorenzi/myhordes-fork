<?php

namespace MyHordes\Plugins;

use MyHordes\Plugins\DependencyInjection\Compiler\ConfigAssembly;
use MyHordes\Plugins\DependencyInjection\Compiler\FixtureChain;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MyHordesPluginsBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass( new FixtureChain() );
        $container->addCompilerPass( new ConfigAssembly() );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $loader->load('services.yaml');

    }
}