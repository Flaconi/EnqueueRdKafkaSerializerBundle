<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection\CompilerPass;

use Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\CompilerPass\KafkaMessageSerializerPass;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\CompilerPass\KafkaMessageSerializerPass
 */
class KafkaMessageSerializerPassTest extends AbstractCompilerPassTestCase
{
    public function testProcess() : void
    {
        $this->container->setParameter('enqueue_rdkafka_serializer.serializer', ['foo' => ['serializer' => 'fooSerializer', 'processor' => 'FooProcessor']]);

        $this->container->setDefinition('enqueue.client.foo.context', new Definition(null, [[]]));

        $this->container->setDefinition('enqueue.transport.foo.context', new Definition(null, [[]]));

        $fooSerializer = new Definition(null, [[]]);

        $this->container->setDefinition('fooSerializer', $fooSerializer);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.client.foo.context', 'setSerializer', [$fooSerializer]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.transport.foo.context', 'setSerializer', [$fooSerializer]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('fooSerializer', 'setProcessorName', ['FooProcessor']);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));
    }

    public function testProcessWithAvroSerializer() : void
    {
        $this->container->setParameter('enqueue_rdkafka_serializer.serializer', ['foo' => ['serializer' => AvroSerializer::class, 'processor' => 'FooProcessor', 'schema_name' => 'bar']]);

        $client    = new Definition(null, [[]]);
        $transport = new Definition(null, [[]]);

        $this->container->setDefinition('enqueue.client.foo.context', $client);

        $this->container->setDefinition('enqueue.transport.foo.context', $transport);

        $fooSerializer = new Definition(AvroSerializer::class);
        $fooSerializer->setArguments([
            new Reference('enqueue_rdkafka_serializer.record_serializer'),
            new Reference('enqueue_rdkafka_serializer.cached_registry'),
            'bar',
        ]);
        $fooSerializer->setPublic(false);
        $fooSerializer->addMethodCall('setProcessorName', ['FooProcessor']);

        $this->compile();

        self::assertEquals($fooSerializer, $client->getMethodCalls()[0][1][0]);
        self::assertEquals($fooSerializer, $transport->getMethodCalls()[0][1][0]);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));
    }

    public function testProcessWithAvroSerializerExisting() : void
    {
        $this->container->setParameter('enqueue_rdkafka_serializer.serializer', ['foo' => ['serializer' => AvroSerializer::class, 'processor' => 'FooProcessor', 'schema_name' => 'bar']]);

        $client    = new Definition(null, [[]]);
        $transport = new Definition(null, [[]]);

        $this->container->setDefinition('enqueue.client.foo.context', $client);

        $this->container->setDefinition('enqueue.transport.foo.context', $transport);

        $fooSerializer = new Definition(AvroSerializer::class);
        $fooSerializer->setArgument('$schemaName', 'bar');
        $fooSerializer->setAutoconfigured(true);
        $fooSerializer->setAutowired(true);
        $fooSerializer->addMethodCall('setProcessorName', ['FooProcessor']);

        $fooSerializer = new Definition(null, [[]]);

        $this->container->setDefinition(AvroSerializer::class, $fooSerializer);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.client.foo.context', 'setSerializer', [$fooSerializer]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.transport.foo.context', 'setSerializer', [$fooSerializer]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(AvroSerializer::class, 'setProcessorName', ['FooProcessor']);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));
    }

    public function testProcessWithNotExistingContext() : void
    {
        $fooSerializer = new Definition(null, [[]]);

        $this->container->setDefinition('enqueue_rdkafka_serializer.serializer', $fooSerializer);

        $this->container->setParameter('enqueue_rdkafka_serializer.serializer', ['foo' => ['serializer' => 'fooSerializer', 'processor' => 'FooProcessor']]);

        $this->compile();

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));
    }

    public function testProcessWithMultipleSerializer() : void
    {
        $fooSerializer = new Definition(null, [[]]);

        $this->container->setDefinition('enqueue_rdkafka_serializer.serializer', $fooSerializer);

        $serializer = [
            'foo' => ['serializer' => 'fooSerializer', 'processor' => 'FooProcessor'],
            'foo2' => ['serializer' => 'foo2Serializer', 'processor' => 'Foo2Processor'],
            'foo3' => ['serializer' => 'foo3Serializer', 'processor' => 'Foo3Processor'],
        ];

        $this->container->setParameter('enqueue_rdkafka_serializer.serializer', $serializer);

        $this->container->setDefinition('enqueue.client.foo.context', new Definition(null, [[]]));
        $this->container->setDefinition('enqueue.client.foo2.context', new Definition(null, [[]]));
        $this->container->setDefinition('enqueue.client.foo3.context', new Definition(null, [[]]));

        $fooSerializer  = new Definition(null, [[]]);
        $foo2Serializer = new Definition(null, [[]]);
        $foo3Serializer = new Definition(null, [[]]);

        $this->container->setDefinition('fooSerializer', $fooSerializer);
        $this->container->setDefinition('foo2Serializer', $foo2Serializer);
        $this->container->setDefinition('foo3Serializer', $foo3Serializer);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('fooSerializer', 'setProcessorName', ['FooProcessor']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.client.foo.context', 'setSerializer', [$fooSerializer]);

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('foo2Serializer', 'setProcessorName', ['Foo2Processor']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.client.foo2.context', 'setSerializer', [$foo2Serializer]);

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('foo3Serializer', 'setProcessorName', ['Foo3Processor']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.client.foo3.context', 'setSerializer', [$foo3Serializer]);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));
    }

    public function testProcessWithMultipleSerializerOneContextMissing() : void
    {
        $fooSerializer = new Definition(null, [[]]);

        $this->container->setDefinition('enqueue_rdkafka_serializer.serializer', $fooSerializer);

        $serializer = [
            'foo' => ['serializer' => 'fooSerializer', 'processor' => 'FooProcessor'],
            'foo2' => ['serializer' => 'foo2Serializer', 'processor' => 'Foo2Processor'],
            'foo3' => ['serializer' => 'foo3Serializer', 'processor' => 'Foo3Processor'],
        ];

        $this->container->setParameter('enqueue_rdkafka_serializer.serializer', $serializer);

        $this->container->setDefinition('enqueue.client.foo.context', new Definition(null, [[]]));
        $this->container->setDefinition('enqueue.client.foo3.context', new Definition(null, [[]]));

        $fooSerializer  = new Definition(null, [[]]);
        $foo3Serializer = new Definition(null, [[]]);

        $this->container->setDefinition('fooSerializer', $fooSerializer);
        $this->container->setDefinition('foo3Serializer', $foo3Serializer);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('fooSerializer', 'setProcessorName', ['FooProcessor']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.client.foo.context', 'setSerializer', [$fooSerializer]);

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('foo3Serializer', 'setProcessorName', ['Foo3Processor']);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('enqueue.client.foo3.context', 'setSerializer', [$foo3Serializer]);

        self::assertFalse($this->container->hasParameter('enqueue_rdkafka_serializer.serializer'));
    }

    /**
     * Register the compiler pass under test, just like you would do inside a bundle's load()
     * method:.
     *
     *   $container->addCompilerPass(new MyCompilerPass());
     */
    protected function registerCompilerPass(ContainerBuilder $container) : void
    {
        $container->addCompilerPass(new KafkaMessageSerializerPass());
    }
}
