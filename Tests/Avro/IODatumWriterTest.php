<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\Avro;

use AvroException;
use AvroIOBinaryEncoder;
use AvroIOException;
use AvroIOTypeException;
use AvroPrimitiveSchema;
use AvroSchema;
use AvroSchemaParseException;
use AvroStringIO;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeZone;
use Flaconi\EnqueueRdKafkaSerializerBundle\Avro\IODatumWriter;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\StringsException;
use function bin2hex;
use function Safe\hex2bin;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\Avro\IODatumWriter
 */
final class IODatumWriterTest extends TestCase
{
    private $writer;

    /**
     * @throws AvroIOTypeException
     * @throws AvroException
     * @throws AvroIOException
     * @throws AvroSchemaParseException
     * @throws StringsException
     *
     * @dataProvider getBigDecimals
     */
    public function testWriteBigDecimal(BigDecimal $decimal, string $result) : void
    {
        $io = new AvroStringIO('');

        $encoder = new AvroIOBinaryEncoder($io);

        $schema = new AvroPrimitiveSchema(AvroSchema::BYTES_TYPE, 'decimal', ['scale' => 3]);

        $this->writer->write_data($schema, $decimal, $encoder);

        self::assertEquals(hex2bin($result), $io->string());
    }

    /**
     * @return array<array<BigDecimal|string>>
     */
    public function getBigDecimals() : array
    {
        return [
            [BigDecimal::ofUnscaledValue(-71900, 3), '06fee724'],
            [BigDecimal::ofUnscaledValue(-35900, 3), '06ff73c4'],
            [BigDecimal::zero(), '0200'],
            [BigDecimal::ofUnscaledValue(203600, 3), '06031b50'],
        ];
    }

    public function testWriteLogicalTypeDecimal() : void
    {
        $io = new AvroStringIO('');

        $encoder = new AvroIOBinaryEncoder($io);

        $schema = new AvroPrimitiveSchema(AvroSchema::BYTES_TYPE, 'decimal', ['scale' => 3]);

        $this->writer->write_data($schema, hex2bin('fee724'), $encoder);

        self::assertEquals(hex2bin('06fee724'), $io->string());
    }

    public function testWriteDatetime() : void
    {
        $io = new AvroStringIO('');

        $encoder = new AvroIOBinaryEncoder($io);

        $datum = (new DateTimeImmutable())
            ->setDate(2019, 2, 28)
            ->setTimezone(new DateTimeZone('UTC'))
            ->setTime(13, 17, 32);

        $schema = <<<JSON
{ "type": "record",
  "name": "dummy",
  "fields" : [
      {"name": "long", "type": ["null",{ "type": "long", "logicalType": "timestamp-millis" }]}
      ]}
JSON;

        $this->writer->write_data(AvroSchema::parse($schema), ['long' => $datum], $encoder);

        self::assertEquals('02c0f785c4a65a', bin2hex($io->string()));
    }

    public function testWriteLogicalTypeTimestamp() : void
    {
        $io = new AvroStringIO('');

        $encoder = new AvroIOBinaryEncoder($io);

        $schema = new AvroPrimitiveSchema(AvroSchema::LONG_TYPE, 'timestamp-millis');

        $this->writer->write_data($schema, 1551359852000, $encoder);

        self::assertEquals('c0f785c4a65a', bin2hex($io->string()));
    }

    public function testWriteLongArray() : void
    {
        $io = new AvroStringIO('');

        $encoder = new AvroIOBinaryEncoder($io);

        $schema = <<<JSON
{
"type": "array",
"items": {
    "name": "longValue",
    "type": "long"
}
}
JSON;

        $this->writer->write_data(AvroSchema::parse($schema), [1551359852000], $encoder);

        self::assertEquals('02c0f785c4a65a00', bin2hex($io->string()));
    }

    public function testWriteLong() : void
    {
        $io = new AvroStringIO('');

        $encoder = new AvroIOBinaryEncoder($io);

        $schema = <<<JSON
{ "type": "record",
  "name": "dummy",
  "fields" : [
      {"name": "long", "type": ["null","long"]}
      ]}
JSON;

        $this->writer->write_data(AvroSchema::parse($schema), ['long' => 1551359852000], $encoder);

        self::assertEquals('02c0f785c4a65a', bin2hex($io->string()));
    }

    public function testWriteRecordWithNotExistingFields() : void
    {
        $io = new AvroStringIO('');

        $encoder = new AvroIOBinaryEncoder($io);

        $schema = <<<JSON
{ "type": "record",
  "name": "dummy",
  "fields" : [
      {"name": "dummy2", "type": "int"},
      {"name": "long", "type": ["null","long"]},
      {"name": "dummy", "type": "int"}
      ]}
JSON;

        $this->expectException(AvroIOTypeException::class);

        $this->writer->write_data(AvroSchema::parse($schema), ['long' => 1551359852000], $encoder);
    }

    protected function setUp() : void
    {
        $this->writer = new IODatumWriter();
    }
}
