<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Serializer;

use Enqueue\RdKafka\Serializer;

interface ProcessorSerializer extends Serializer
{
    public function setProcessorName(string $processorName) : void;
}
