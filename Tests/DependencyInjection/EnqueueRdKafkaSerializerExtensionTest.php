<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\DependencyInjection;

use Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\EnqueueRdKafkaSerializerExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\ProcessorSerializer;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
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
