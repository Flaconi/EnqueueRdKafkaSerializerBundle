<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection;

use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\BigDecimalConverterExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\ImmutableDateTimeConverterExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use GuzzleHttp\Client;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\PropertyAccess\PropertyAccess;
use function array_key_exists;
use function count;

final class EnqueueRdKafkaSerializerExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $this->setSerializer($config, $container);

        // extensions
        $this->loadExtension($config, $container, 'big_decimal_converter', BigDecimalConverterExtension::class);
        $this->loadExtension(
            $config,
            $container,
            'immutable_datetime_converter',
            ImmutableDateTimeConverterExtension::class,
        );
    }

    public function getAlias() : string
    {
        return 'enqueue_rdkafka_serializer';
    }

    /**
     * @param array<array<string>> $config
     */
    private function setSerializer(array $config, ContainerBuilder $container) : void
    {
        if (! array_key_exists('serializer', $config) || count($config['serializer']) === 0) {
            return;
        }

        $avroConfig = $config['avro'];

        foreach ($config['serializer'] as $serializerConfig) {
            if ($serializerConfig['serializer'] === AvroSerializer::class) {
                $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
                $loader->load('avro.xml');

                $schemaRegistryClient = new Definition(Client::class);
                $schemaRegistryClient->setPublic(false);
                $schemaRegistryClient->setArgument(0, ['base_uri' => $avroConfig['schema_registry']]);

                $container->getDefinition('enqueue_rdkafka_serializer.promising_registry')
                    ->setArgument(0, $schemaRegistryClient);

                $writerDef = new Definition($avroConfig['avro_io_writer']);
                $writerDef->setPublic(false);

                $readerDef = new Definition($avroConfig['avro_io_reader']);
                $readerDef->setPublic(false);

                $container->getDefinition('enqueue_rdkafka_serializer.record_serializer')
                    ->setArgument(1, $writerDef)
                    ->setArgument(2, $readerDef)
                    ->setArgument(3,
                        [
                            RecordSerializer::OPTION_REGISTER_MISSING_SCHEMAS => $avroConfig['register_missing_schemas'],
                            RecordSerializer::OPTION_REGISTER_MISSING_SUBJECTS => $avroConfig['register_missing_subjects'],
                        ]
                    );

                break;
            }
        }

        $container->setParameter('enqueue_rdkafka_serializer.serializer', $config['serializer']);
    }

    /**
     * @param array<array<array<array<(string|bool)>>>> $config
     */
    private function loadExtension(array $config, ContainerBuilder $container, string $extensionName, string $class) : void
    {
        $extensionConfig = $config['extensions'][$extensionName];

        if (! $extensionConfig['enabled'] || count($extensionConfig['context']) === 0) {
            return;
        }

        $extension = $container->register($class, $class);
        $extension->setArgument('$convertibleProperties', $extensionConfig['convertibleProperties']);
        $extension->setArgument('$format', $extensionConfig['format']);

        $propertyAccessorDef = new Definition();
        $propertyAccessorDef->setFactory([PropertyAccess::class, 'createPropertyAccessor']);
        $propertyAccessorDef->setPublic(false);

        $extension->setArgument('$propertyAccessor', $propertyAccessorDef);
        foreach ($extensionConfig['context'] as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }
}
