<?php
namespace TheRat\CronControl\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('cron_control');

        $rootNode
            ->children()
                ->scalarNode('disable_postfix')->cannotBeEmpty()->defaultValue('.disabled')->end()
                ->arrayNode('logger')
                    ->children()
                        ->scalarNode('level')->cannotBeEmpty()->defaultValue('NOTICE')->end()
                        ->scalarNode('filename')->end()
                    ->end()
                ->end()
                ->arrayNode('glob_patterns')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('mailer')
                    ->children()
                        ->enumNode('transport')
                            ->values(array('mail', 'smtp', ''))
                        ->end()
                        ->scalarNode('host')->end()
                        ->integerNode('port')->defaultValue(25)->end()
                        ->scalarNode('security')->end()
                        ->scalarNode('username')->end()
                        ->scalarNode('password')->end()
                        ->scalarNode('sender_name')->defaultValue('Cron Control')->end()
                        ->scalarNode('sender_email')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
