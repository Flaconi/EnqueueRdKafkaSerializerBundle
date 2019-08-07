<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\DependencyInjection;

use Flaconi\EnqueueRdKafkaSerializerBundle\Avro\IODatumReader;
use Flaconi\EnqueueRdKafkaSerializerBundle\Avro\IODatumWriter;
use Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\EnqueueRdKafkaSerializerExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\BigDecimalConverterExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\ImmutableDateTimeConverterExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\ProcessorSerializer;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use GuzzleHttp\Client;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\EnqueueRdKafkaSerializerExtension
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\Configuration
 */
class EnqueueRdKafkaSerializerExtensionTest extends AbstractExtensionTestCase
{
    public function testCorrectParameterSetAfterLoading() : void
    {
        $config = [
            'serializer' => [
                'foo' => [
                    'serializer' => ProcessorSerializer::class,
                    'processor' => NullProcessor::class,
                ],
            ],
        ];

        $this->load($config);

        $this->assertContainerBuilderHasParameter('enqueue_rdkafka_serializer.serializer', $config['serializer']);
        $this->assertContainerBuilderNotHasService(BigDecimalConverterExtension::class);
        $this->assertContainerBuilderNotHasService(ImmutableDateTimeConverterExtension::class);
    }

    /**
     * @dataProvider getExtensions
     */
    public function testExtensionsAfterLoadingWithNoContextSet(string $extensionName, string $serviceId) : void
    {
        $config = [
            'serializer' => false,
            'extensions' => [
                $extensionName => [
                    'format' => 'foobar',
                    'context' => [],
                ],
            ],
        ];

        $this->load($config);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));

        $this->assertContainerBuilderNotHasService($serviceId);
    }

    /**
     * @dataProvider getExtensions
     */
    public function testExtensionsAfterLoading(string $extensionName, string $serviceId) : void
    {
        $config = [
            'serializer' => false,
            'extensions' => [
                $extensionName => [
                    'format' => 'foobar',
                    'context' => ['name'],
                ],
            ],
        ];

        $this->load($config);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));

        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, '$convertibleProperties', []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, '$format', 'foobar');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, '$propertyAccessor', (new Definition())->setFactory([PropertyAccess::class, 'createPropertyAccessor'])->setPublic(false));
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, 'enqueue.consumption_extension', ['client' => 'name']);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, 'enqueue.transport.consumption_extension', ['transport' => 'name']);
    }

    /**
     * @return array<array<string>>
     */
    public function getExtensions() : array
    {
        return [
            ['big_decimal_converter', BigDecimalConverterExtension::class],
            ['immutable_datetime_converter', ImmutableDateTimeConverterExtension::class],
        ];
    }

    public function testErrorAfterLoadingForAvroSerializerWithoutSchema() : void
    {
        $config = [
            'serializer' => [
                'foo' => [
                    'serializer' => AvroSerializer::class,
                    'processor' => NullProcessor::class,
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "enqueue_rdkafka_serializer.serializer.foo": When AvroSerializer is used the schema_name needs to be set');

        $this->load($config);
    }

    public function testErrorAfterLoadingForAvroSerializerWithoutSchemaRegistry() : void
    {
        $config = [
            'serializer' => [
                'foo' => [
                    'serializer' => AvroSerializer::class,
                    'processor' => NullProcessor::class,
                    'schema_name' => 'dummy-value',
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "enqueue_rdkafka_serializer": When AvroSerializer is used avro schema registry needs to be set');

        $this->load($config);
    }

    public function testCorrectParameterSetAfterLoadingForAvroSerializer() : void
    {
        $config = [
            'avro' => [
                'enabled' => true,
                'schema_registry' => 'http://schmema-registry',
            ],
            'serializer' => [
                'foo' => [
                    'serializer' => AvroSerializer::class,
                    'processor' => NullProcessor::class,
                    'schema_name' => 'dummy-value',
                ],
            ],
        ];

        $this->load($config);

        $schemaRegistryClient = new Definition(Client::class, [['base_uri' => 'http://schmema-registry']]);
        $schemaRegistryClient->setPublic(false);

        $writerDef = new Definition(IODatumWriter::class);
        $writerDef->setPublic(false);

        $readerDef = new Definition(IODatumReader::class);
        $readerDef->setPublic(false);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('enqueue_rdkafka_serializer.promising_registry', 0, $schemaRegistryClient);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('enqueue_rdkafka_serializer.record_serializer', 1, $writerDef);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('enqueue_rdkafka_serializer.record_serializer', 2, $readerDef);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('enqueue_rdkafka_serializer.record_serializer', 3, [
            RecordSerializer::OPTION_REGISTER_MISSING_SCHEMAS => true,
            RecordSerializer::OPTION_REGISTER_MISSING_SUBJECTS => false,
        ]);

        $this->assertContainerBuilderHasParameter('enqueue_rdkafka_serializer.serializer', $config['serializer']);
    }

    public function testParameterHasNotBeenSetAfterLoading() : void
    {
        $config = [];

        $this->load($config);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));
    }

    /**
     * @return array<ExtensionInterface>
     */
    protected function getContainerExtensions() : array
    {
        return [
            new EnqueueRdKafkaSerializerExtension(),
        ];
    }
}
