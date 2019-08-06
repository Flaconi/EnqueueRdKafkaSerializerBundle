<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\CompilerPass;

use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function Safe\sprintf;

final class KafkaMessageSerializerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        if (! $container->hasParameter('enqueue_rdkafka_serializer.serializer')) {
            return;
        }

        foreach (['client', 'transport'] as $type) {
            foreach ($container->getParameter('enqueue_rdkafka_serializer.serializer') as $contextName => $config) {
                $contextServiceId = sprintf('enqueue.%s.%s.context', $type, $contextName);

                if (! $container->has($contextServiceId)) {
                    continue;
                }

                $contextService = $container->findDefinition($contextServiceId);

                $serializerDefinition = null;

                if ($config['serializer'] === AvroSerializer::class && ! $container->hasDefinition(
                    $config['serializer'],
                )) {
                    $serializerDefinition = new Definition($config['serializer']);
                    $serializerDefinition->setPublic(false);
                    $serializerDefinition->setArguments(
                        [
                            new Reference('enqueue_rdkafka_serializer.record_serializer'),
                            new Reference('enqueue_rdkafka_serializer.cached_registry'),
                            $config['schema_name'],
                        ],
                    );
                }

                if ($serializerDefinition === null) {
                    $serializerDefinition = $container->findDefinition($config['serializer']);
                }

                $serializerDefinition->addMethodCall('setProcessorName', [$config['processor']]);

                $contextService->addMethodCall('setSerializer', [$serializerDefinition]);
            }
        }

        $container->getParameterBag()->remove('enqueue_rdkafka_serializer.serializer');
    }
}
