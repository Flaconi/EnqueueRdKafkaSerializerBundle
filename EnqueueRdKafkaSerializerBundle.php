<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle;

use Flaconi\EnqueueRdKafkaSerializerBundle\DependencyInjection\EnqueueRdKafkaSerializerExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EnqueueRdKafkaSerializerBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new EnqueueRdKafkaSerializerExtension();
    }
}
