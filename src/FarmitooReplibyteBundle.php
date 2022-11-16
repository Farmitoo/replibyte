<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FarmitooReplibyteBundle extends AbstractBundle
{
    protected string $extensionAlias = "farmitoo_replibyte";

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode("databases")
                    ->children()
                        ->arrayNode("distant")
                            ->children()
                                ->scalarNode("host")->end()
                                ->scalarNode("user")->end()
                                ->scalarNode("name")->end()
                                ->scalarNode("password")->end()
                            ->end()
                        ->end()
                        ->arrayNode("local")
                            ->children()
                                ->scalarNode("host")->end()
                                ->scalarNode("user")->end()
                                ->scalarNode("name")->end()
                                ->scalarNode("password")->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import("../config/services.yml");
        $container->services()
            ->get("Farmitoo\\ReplibyteBundle\\Connection\\DistantPDOConnection")
            ->arg("\$dbHost", $config["databases"]["distant"]["host"])
            ->arg("\$dbName", $config["databases"]["distant"]["name"])
            ->arg("\$dbUser", $config["databases"]["distant"]["user"])
            ->arg("\$dbPassword", $config["databases"]["distant"]["password"])
        ;
        $container->services()
            ->get("Farmitoo\\ReplibyteBundle\\Connection\\LocalPDOConnection")
            ->arg("\$dbHost", $config["databases"]["local"]["host"])
            ->arg("\$dbName", $config["databases"]["local"]["name"])
            ->arg("\$dbUser", $config["databases"]["local"]["user"])
            ->arg("\$dbPassword", $config["databases"]["local"]["password"])
        ;
    }
}
