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
                                ->scalarNode("host")->isRequired()->end()
                                ->scalarNode("user")->isRequired()->end()
                                ->scalarNode("name")->isRequired()->end()
                                ->scalarNode("password")->isRequired()->end()
                            ->end()
                        ->end()
                        ->arrayNode("local")
                            ->children()
                                ->scalarNode("host")->isRequired()->end()
                                ->scalarNode("user")->isRequired()->end()
                                ->scalarNode("name")->isRequired()->end()
                                ->scalarNode("password")->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode("force_table_constraints")
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode("REFERENCED_TABLE_NAME")->isRequired()->end()
                            ->scalarNode("REFERENCED_COLUMN_NAME")->isRequired()->end()
                            ->scalarNode("TABLE_NAME")->isRequired()->end()
                            ->scalarNode("COLUMN_NAME")->isRequired()->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode("table_custom_configuration")
                    ->useAttributeAsKey("name")
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode("limit")->end()
                            ->arrayNode("data")
                                ->scalarPrototype()->end()
                            ->end()
                            ->booleanNode("disabled")->end()
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
        $container->parameters()
            ->set("replibyte_force_table_constraints", $config["force_table_constraints"])
            ->set("replibyte_table_custom_configuration", $config["table_custom_configuration"])
        ;
    }
}
