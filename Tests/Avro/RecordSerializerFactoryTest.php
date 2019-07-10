<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\Avro;

use AvroIOBinaryEncoder;
use AvroIODatumReader;
use AvroIODatumWriter;
use AvroSchema;
use Flaconi\EnqueueRdKafkaSerializerBundle\Avro\RecordSerializerFactory;
use FlixTech\SchemaRegistryApi\Registry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use function bin2hex;
use function hex2bin;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\Avro\RecordSerializerFactory
 */
final class RecordSerializerFactoryTest extends TestCase
{
    private $registry;
    private $writer;
    private $reader;
    private $schema;

    private const TEST_RECORD = [
        'name' => 'Thomas',
        'age' => 36,
    ];

    private const HEX_BIN = '000000270f0c54686f6d617348';

    private const SCHEMA_JSON = /** @lang JSON */
        <<<JSON
{
  "type": "record",
  "name": "user",
  "fields": [
    {"name": "name", "type": "string"},
    {"name": "age", "type": "int"}
  ]
}
JSON;

    public function testFactoryGet() : void
    {
        $this->registry->schemaForId(9999)->willReturn($this->schema)->shouldBeCalledOnce();

        $this->reader->read_data(Argument::any(), Argument::any(), Argument::any())->willReturn('foobar')->shouldBeCalledOnce();

        $serializer = RecordSerializerFactory::get($this->registry->reveal(), $this->writer->reveal(), $this->reader->reveal());

        self::assertSame('foobar', $serializer->decodeMessage(hex2bin(self::HEX_BIN)));
    }

    public function testFactoryGetWriter() : void
    {
        $this->writer->write_data(Argument::any(), Argument::any(), Argument::that(static function (AvroIOBinaryEncoder $avroIOBinaryEncoder) {
            $avroIOBinaryEncoder->write('test');

            return true;
        }))->shouldBeCalledOnce();

        $this->registry->schemaId('test', $this->schema)->willReturn(9999)->shouldBeCalledOnce();

        $serializer = RecordSerializerFactory::get($this->registry->reveal(), $this->writer->reveal(), $this->reader->reveal());

        self::assertSame('000000270f74657374', bin2hex($serializer->encodeRecord('test', $this->schema, self::TEST_RECORD)));
    }

    protected function setUp() : void
    {
        $this->writer   = $this->prophesize(AvroIODatumWriter::class);
        $this->reader   = $this->prophesize(AvroIODatumReader::class);
        $this->registry = $this->prophesize(Registry::class);
        $this->schema   = AvroSchema::parse(self::SCHEMA_JSON);
    }
}
