<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection;

use Enqueue\RdKafka\Serializer;
use Flaconi\EnqueueRdKafkaSerializerBundle\Avro\IODatumReader;
use Flaconi\EnqueueRdKafkaSerializerBundle\Avro\IODatumWriter;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer;
use Interop\Queue\Processor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function array_key_exists;
use function is_subclass_of;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder() : TreeBuilder
    {
        $treeBuilder = new TreeBuilder('enqueue_rdkafka_serializer');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $this->addSerializerSection($rootNode);

        return $treeBuilder;
    }

    private function addSerializerSection(ArrayNodeDefinition $node) : void
    {
        $isNotProcessor = static function ($v) {
            return ! is_subclass_of($v, Processor::class);
        };

        $isNotSerializer = static function ($v) {
            return ! is_subclass_of($v, Serializer::class);
        };

        $isAvroSerializerWithoutSchemaName = static function ($v) {
            return $v['serializer'] === AvroSerializer::class && ! array_key_exists('schema_name', $v);
        };

        $isAvroSerializerWithMissingConfig = static function ($v) {
            if (! array_key_exists('serializer', $v)) {
                return false;
            }

            $isAvroConfigEnabled = array_key_exists('avro', $v) && array_key_exists('schema_registry', $v['avro']);

            foreach ($v['serializer'] as $key => $value) {
                if ($value['serializer'] === AvroSerializer::class) {
                    return ! $isAvroConfigEnabled;
                }
            }

            return false;
        };

        $node
            ->validate()
                ->ifTrue($isAvroSerializerWithMissingConfig)
                ->thenInvalid('When AvroSerializer is used avro schema registry needs to be set')
            ->end()
            ->children()
                ->arrayNode('avro')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('schema_registry')
                            ->isRequired()
                        ->end()
                        ->scalarNode('avro_io_writer')->defaultValue(IODatumWriter::class)->end()
                        ->scalarNode('avro_io_reader')->defaultValue(IODatumReader::class)->end()
                        ->scalarNode('register_missing_schemas')->defaultValue(true)->end()
                        ->scalarNode('register_missing_subjects')->defaultValue(false)->end()
                    ->end()
                ->end()
                ->arrayNode('extensions')
                    ->canBeEnabled()
                    ->children()
                        ->append($this->addExtension('big_decimal_converter'))
                        ->append($this->addExtension('immutable_datetime_converter'))
                    ->end()
                ->end()
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
                        ->ifTrue($isAvroSerializerWithoutSchemaName)
                        ->thenInvalid('When AvroSerializer is used the schema_name needs to be set')
                    ->end()
                ->end()
            ->end();
    }

    private function addExtension(string $extensionName) : ArrayNodeDefinition
    {
        return (new ArrayNodeDefinition($extensionName))
            ->canBeEnabled()
            ->children()
                ->scalarNode('format')->isRequired()->end()
                ->arrayNode('context')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('convertibleProperties')
                    ->prototype('scalar')->end()
                ->end()
            ->end();
    }
}
