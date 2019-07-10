<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\Avro;

use AvroException;
use AvroIOBinaryDecoder;
use AvroIOBinaryEncoder;
use AvroIOException;
use AvroIOSchemaMatchException;
use AvroPrimitiveSchema;
use AvroSchema;
use AvroSchemaParseException;
use AvroStringIO;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeZone;
use Flaconi\EnqueueRdKafkaSerializerBundle\Avro\IODatumReader;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\StringsException;
use function Safe\hex2bin;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\Avro\IODatumReader
 */
final class IODatumReaderTest extends TestCase
{
    private $reader;

    /**
     * @throws AvroException
     * @throws AvroIOException
     * @throws AvroIOSchemaMatchException
     * @throws AvroSchemaParseException
     * @throws StringsException
     *
     * @dataProvider getBigDecimals
     */
    public function testReadDataForBigDecimal(BigDecimal $decimal, string $hexCode) : void
    {
        $schema = new AvroPrimitiveSchema(AvroSchema::BYTES_TYPE, 'decimal', ['scale' => 4]);

        self::assertEquals(
            $decimal,
            $this->reader->read_data($schema, $schema, new AvroIOBinaryDecoder(new AvroStringIO(hex2bin($hexCode)))),
        );
    }

    /**
     * @return array<array<BigDecimal|string>>
     */
    public function getBigDecimals() : array
    {
        return [
            [BigDecimal::ofUnscaledValue(203600, 4), '06031b50'],
            [BigDecimal::ofUnscaledValue(-71900, 4), '06fee724'],
            [BigDecimal::ofUnscaledValue(-35900, 4), '06ff73c4'],
            [BigDecimal::zero()->toScale(4), '0200'],
        ];
    }

    public function testReadDataForDateTime() : void
    {
        $schema = new AvroPrimitiveSchema(AvroSchema::LONG_TYPE, 'timestamp-millis');

        $dateTime = (new DateTimeImmutable())
            ->setDate(2019, 2, 28)
            ->setTimezone(new DateTimeZone('UTC'))
            ->setTime(13, 17, 32);

        self::assertEquals(
            $dateTime,
            $this->reader->read_data($schema, $schema, new AvroIOBinaryDecoder(new AvroStringIO(hex2bin('c0f785c4a65a')))),
        );
    }

    public function testReadDataForNoneLogicalTypes() : void
    {
        $oi = new AvroStringIO('');

        (new AvroIOBinaryEncoder($oi))->write_long(1551359852000);

        $schema = new AvroPrimitiveSchema(AvroSchema::LONG_TYPE);

        self::assertEquals(
            1551359852000,
            $this->reader->read_data($schema, $schema, new AvroIOBinaryDecoder(new AvroStringIO($oi->__toString()))),
        );
    }

    protected function setUp() : void
    {
        $this->reader = new IODatumReader();
    }
}
