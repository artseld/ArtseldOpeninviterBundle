<?php

/*
 * This file is part of the Artseld\OpeninviterBundle package.
 *
 * (c) Dmitry Kozlovich <artseld@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artseld\OpeninviterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('artseld_openinviter');

        $rootNode
            ->children()
                ->scalarNode('username')
                    ->cannotBeOverwritten()->isRequired()->cannotBeEmpty()
                ->end()
                ->scalarNode('private_key')
                    ->cannotBeOverwritten()->isRequired()->cannotBeEmpty()
                ->end()
                ->scalarNode('plugins_cache_time')
                    ->defaultValue('1800')->cannotBeEmpty()
                ->end()
                ->scalarNode('plugins_cache_file')
                    ->defaultValue('oi_plugins.php')->isRequired()->cannotBeEmpty()
                ->end()
                ->scalarNode('cookie_path')
                    ->defaultValue('/tmp')->isRequired()->cannotBeEmpty()
                ->end()
                ->scalarNode('local_debug')
                    ->defaultFalse()
                ->end()
                ->scalarNode('remote_debug')
                    ->defaultFalse()
                ->end()
                ->scalarNode('hosted')
                    ->defaultFalse()
                ->end()
                ->arrayNode('proxies')
                    ->defaultValue(array())->cannotBeEmpty()
                ->end()
                ->scalarNode('stats')
                    ->defaultFalse()
                ->end()
                ->scalarNode('stats_user')
                    ->defaultFalse()
                ->end()
                ->scalarNode('stats_password')
                    ->defaultFalse()
                ->end()
                ->scalarNode('update_files')
                    ->defaultFalse()->cannotBeEmpty()
                ->end()
                ->scalarNode('transport')
                    ->defaultValue('wget')->isRequired()->cannotBeEmpty()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
