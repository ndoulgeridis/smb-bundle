<?php

namespace SMBBundle\DependencyInjection;

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
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('smb');
        
        $rootNode 
            ->children()
                ->scalarNode('host')
                    ->defaultValue('localhost')
                ->end()
                ->scalarNode('user')
                    ->defaultValue('user')
                ->end()
                ->scalarNode('password')
                    ->defaultValue('')
            ->end();
        return $treeBuilder;
    }
}
