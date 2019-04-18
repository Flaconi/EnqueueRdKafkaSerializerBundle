<?php declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection;

use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\BigDecimalConverterExtension;
use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\ImmutableDateTimeConverterExtension;
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

        $this->setSerializer($config, $container);

        // extensions
        $this->loadExtension($config, $container, 'big_decimal_converter', BigDecimalConverterExtension::class);
        $this->loadExtension(
            $config,
            $container,
            'immutable_datetime_converter',
            ImmutableDateTimeConverterExtension::class
        );
    }

    private function setSerializer(array $config, ContainerBuilder $container) : void
    {
        if (! array_key_exists('serializer', $config) || count($config['serializer']) === 0) {
            return;
        }

        $container->setParameter('enqueue_rdkafka_serializer.serializer', $config['serializer']);
    }

    private function loadExtension(
        array $config,
        ContainerBuilder $container,
        string $extensionName,
        string $class
    ) : void {
        $extensionConfig = $config['extensions'][$extensionName];

        if (! $extensionConfig['enabled'] || count($extensionConfig['context']) === 0) {
            return;
        }

        $extension = $container->register($class, $class);
        $extension->setArgument('$convertibleProperties', $extensionConfig['convertibleProperties']);
        $extension->setArgument('$format', $extensionConfig['format']);
        foreach ($extensionConfig['context'] as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }

    public function getAlias()
    {
        return 'enqueue_rdkafka_serializer';
    }
}
