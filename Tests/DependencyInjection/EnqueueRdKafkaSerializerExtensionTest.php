<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\DependencyInjection;

use Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\EnqueueRdKafkaSerializerExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\BigDecimalConverterExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\ImmutableDateTimeConverterExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\ProcessorSerializer;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

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
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, 'enqueue.consumption_extension', ['client' => 'name']);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, 'enqueue.transport.consumption_extension', ['transport' => 'name']);
    }

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

    public function testCorrectParameterSetAfterLoadingForAvroSerializer() : void
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

        $this->load($config);

        $this->assertContainerBuilderHasParameter('enqueue_rdkafka_serializer.serializer', $config['serializer']);
    }

    public function testParameterHasNotBeenSetAfterLoading() : void
    {
        $config = [];

        $this->load($config);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions() : array
    {
        return [
            new EnqueueRdKafkaSerializerExtension(),
        ];
    }
}
