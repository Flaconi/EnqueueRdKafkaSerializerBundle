<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection;

use Enqueue\RdKafka\Serializer;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer;
use Interop\Queue\Processor;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function is_subclass_of;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder() : TreeBuilder
    {
        $treeBuilder = new TreeBuilder('enqueue_rdkafka_serializer');
        $rootNode    = $treeBuilder->getRootNode();

        $this->addSerializerSection($rootNode);

        return $treeBuilder;
    }

    private function addSerializerSection(NodeDefinition $node) : void
    {
        $isNotProcessor = static function ($v) {
            return ! is_subclass_of($v, Processor::class);
        };

        $isNotSerializer = static function ($v) {
            return ! is_subclass_of($v, Serializer::class);
        };
        $node
            ->children()
                ->arrayNode('serializer')
                ->canBeUnset()
                ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('serializer')
                            ->isRequired()
                            ->validate()
                                ->ifTrue($isNotSerializer)
                                ->thenInvalid('Invalid serializer %s')
                            ->end()
                        ->end()
                        ->scalarNode('processor')
                            ->isRequired()
                            ->validate()
                                ->ifTrue($isNotProcessor)
                                ->thenInvalid('Invalid processor %s')
                            ->end()
                        ->end()
                        ->scalarNode('schema_name')->end()
                    ->end()
                    ->validate()
                        ->ifTrue(static function ($v) {
                            return ($v['serializer'] === AvroSerializer::class) && ! isset($v['schema_name']);
                        })
                        ->thenInvalid('When AvroSerializer is used the schema_name needs to be set')
                    ->end()
                ->end()
            ->end();
    }
}
