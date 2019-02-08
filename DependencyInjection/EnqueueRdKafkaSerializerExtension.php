<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use function array_key_exists;

final class EnqueueRdKafkaSerializerExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        if (! array_key_exists('serializer', $config)) {
            return;
        }

        $container->setParameter('enqueue_rdkafka_serializer.serializer', $config['serializer']);
    }

    public function getAlias()
    {
        return 'enqueue_rdkafka_serializer';
    }
}
