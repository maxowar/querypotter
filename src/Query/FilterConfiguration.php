<?php

namespace QueryPotter\Query;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class FilterConfiguration implements ConfigurationInterface
{

    private array $configuration;

    public function __construct($config = [])
    {
        $processor = new Processor();
        $this->configuration = $processor->processConfiguration(
            $this,
            [$config]
        );
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('querypotter');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('items')
                ->info('The number of elements returned. 0 for unlimited')
                ->validate()
                ->ifTrue(
                    function ($val) { return $val < 0; }
                )
                ->thenInvalid('Minimum list items is 1, 0 for unlimited')
                ->end()
                ->defaultValue(25)
            ->end()
            ->scalarNode('page')
                ->info('The offset page')
                ->validate()
                ->ifTrue(
                    function ($val) { return $val <= 0; }
                )
                ->thenInvalid('Page value must be positive')
                ->end()
                ->defaultValue(1)
            ->end()
            ->arrayNode('order')
                ->arrayPrototype()
                ->children()
                    ->scalarNode('by')->end()
                    ->scalarNode('dir')
                    ->validate()
                        ->ifNotInArray(array('desc', 'asc', 'ASC', 'DESC'))
                        ->thenInvalid('Invalid order direction %s')
                    ->end()
                    ->defaultValue('asc')
                    ->end()
                ->end()
            ->end()
            ->end()
            ->variableNode('filters')
                ->info('A vector of real or virtual field names and values')
            ->end()
            ->arrayNode('operators')
                ->info('Default mapping of fields')
                    ->prototype('scalar')
                    ->validate()
                            ->ifNotInArray(
                                [
                                    'eq',
                                    'neq',
                                    'like',
                                    'between',
                                    'gt',
                                    'gte',
                                    'lt',
                                    'lte',
                                    'in',
                                    'notin',
                                    'isNull',
                                    'isNotNull'
                                ]
                            )
                            ->thenInvalid('Invalid operator %s')
                        ->end()
                    ->end()
                ->end()
            ->scalarNode('group_by')
            ->end()
            ->booleanNode('skip_null_values')
                ->info('If true skip input filter fields with null value')
                ->defaultTrue()
            ->end()
        ;

        return $treeBuilder;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
