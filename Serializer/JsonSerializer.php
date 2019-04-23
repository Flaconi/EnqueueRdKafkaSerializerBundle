<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Serializer;

use Enqueue\Client\Config;
use Enqueue\RdKafka\RdKafkaMessage;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

final class JsonSerializer implements ProcessorSerializer
{
    /** @var DecoderInterface */
    private $decoder;

    /** @var EncoderInterface */
    private $encoder;

    /** @var string */
    private $processorName;

    public function setProcessorName(string $processorName) : void
    {
        $this->processorName = $processorName;
    }

    public function __construct(DecoderInterface $decoder, EncoderInterface $encoder)
    {
        $this->decoder = $decoder;
        $this->encoder = $encoder;
    }

    public function toString(RdKafkaMessage $message) : string
    {
        $properties = $message->getProperties();

        unset($properties[Config::PROCESSOR]);

        return (string) $this->encoder->encode($properties, 'json');
    }

    public function toMessage(string $string) : RdKafkaMessage
    {
        $message = new RdKafkaMessage('', $this->decoder->decode($string, 'json'));

        $message->setProperty(Config::PROCESSOR, $this->processorName);

        return $message;
    }
}
