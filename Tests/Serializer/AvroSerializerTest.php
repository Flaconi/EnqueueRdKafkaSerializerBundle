<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\Serializer;

use AvroSchema;
use Enqueue\RdKafka\RdKafkaMessage;
use Exception;
use Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry;
use GuzzleHttp\Promise\FulfilledPromise;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\Serializer\AvroSerializer
 */
final class AvroSerializerTest extends TestCase
{
    private $recordSerializer;
    private $serializer;
    private $registry;

    public function testToString() : void
    {
        $schema = $this->prophesize(AvroSchema::class);

        $this->registry->latestVersion('schema-name-value')->willReturn(new FulfilledPromise($schema->reveal()));

        $message = new RdKafkaMessage('', ['foo' => 'bar', 'enqueue.processor' => 'processor']);

        $this->recordSerializer->encodeRecord('schema-name-value', $schema->reveal(), ['foo' => 'bar'])->willReturn('encoded');

        self::assertEquals('encoded', $this->serializer->toString($message));
    }

    public function testToStringWithException() : void
    {
        $this->registry->latestVersion('schema-name-value')->willReturn(new Exception());

        $message = new RdKafkaMessage('', ['foo' => 'bar', 'enqueue.processor' => 'processor']);

        $this->expectException(Exception::class);

        $this->serializer->toString($message);
    }

    public function testToMessage() : void
    {
        $msg = new RdKafkaMessage('', ['foo' => 'bar', 'enqueue.processor' => 'processor']);

        $this->recordSerializer->decodeMessage('dummy')->willReturn(['foo' => 'bar']);

        self::assertEquals($msg, $this->serializer->toMessage('dummy'));
    }

    protected function setUp() : void
    {
        $this->recordSerializer = $this->prophesize(RecordSerializer::class);
        $this->registry         = $this->prophesize(Registry::class);
        $this->serializer       = new AvroSerializer($this->recordSerializer->reveal(), $this->registry->reveal(), 'schema-name-value');
        $this->serializer->setProcessorName('processor');
    }
}
