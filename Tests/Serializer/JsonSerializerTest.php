<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\Serializer;

use Enqueue\RdKafka\RdKafkaMessage;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\JsonSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\JsonSerializer
 */
final class JsonSerializerTest extends TestCase
{
    private $decoder;
    private $encoder;
    private $jsonSerializer;

    public function testToString() : void
    {
        $message = new RdKafkaMessage();
        $message->setProperties(['dummy']);

        $this->encoder->encode(['dummy'], 'json')->willReturn('json_string');

        self::assertEquals('json_string', $this->jsonSerializer->toString($message));
    }

    public function testToStringWithInt() : void
    {
        $message = new RdKafkaMessage();
        $message->setProperties(['dummy']);

        $this->encoder->encode(['dummy'], 'json')->willReturn(1);

        self::assertEquals('1', $this->jsonSerializer->toString($message));
    }

    public function testToMessage() : void
    {
        $this->decoder->decode('json_string', 'json')->willReturn(['bind_data' => 'bind_data_json']);

        $message = $this->jsonSerializer->toMessage('json_string');

        self::assertEquals('', $message->getBody());
        self::assertEquals(['bind_data' => 'bind_data_json', 'enqueue.processor' => 'foo'], $message->getProperties());
    }

    protected function setUp() : void
    {
        $this->decoder = $this->prophesize(DecoderInterface::class);
        $this->encoder = $this->prophesize(EncoderInterface::class);

        $this->jsonSerializer = new JsonSerializer($this->decoder->reveal(), $this->encoder->reveal());
        $this->jsonSerializer->setProcessorName('foo');
    }
}
