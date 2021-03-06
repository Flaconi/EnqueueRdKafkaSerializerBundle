<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle;

use Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\CompilerPass\KafkaMessageSerializerPass;
use Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\EnqueueRdKafkaSerializerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore
 */
final class EnqueueRdKafkaSerializerBundle extends Bundle
{
    public function getContainerExtension() : EnqueueRdKafkaSerializerExtension
    {
        return new EnqueueRdKafkaSerializerExtension();
    }

    public function build(ContainerBuilder $container) : void
    {
        $container->addCompilerPass(new KafkaMessageSerializerPass());
    }
}
