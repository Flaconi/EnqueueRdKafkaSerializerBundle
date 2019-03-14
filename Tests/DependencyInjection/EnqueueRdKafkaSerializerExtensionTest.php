<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\DependencyInjection;

use Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\EnqueueRdKafkaSerializerExtension;
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
                    'schema_name' => 'dummy-value'
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
