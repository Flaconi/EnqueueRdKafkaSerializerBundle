<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Avro;

use AvroException;
use AvroIOBinaryDecoder;
use AvroIODatumReader;
use AvroIOSchemaMatchException;
use AvroSchema;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use phpseclib\Math\BigInteger;

final class IODatumReader extends AvroIODatumReader
{
    /**
     * @param AvroSchema          $writers_schema
     * @param AvroSchema          $readers_schema
     * @param AvroIOBinaryDecoder $decoder
     *
     * @return DateTimeImmutable|BigDecimal|bool|int|float|double|string|null
     *
     * @throws AvroException
     * @throws AvroIOSchemaMatchException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function read_data($writers_schema, $readers_schema, $decoder)
    {
        $data = parent::read_data($writers_schema, $readers_schema, $decoder);

        if ($readers_schema->logical_type() === 'decimal') {
            return BigDecimal::ofUnscaledValue(
                (new BigInteger($data, -256))->toString(),
                $readers_schema->extra_attributes()['scale'],
            );
        }

        if ($readers_schema->logical_type() === 'timestamp-millis') {
            $milliSeconds = BigDecimal::ofUnscaledValue($data, 3);

            return DateTimeImmutable::createFromFormat('U.u', (string) $milliSeconds);
        }

        return $data;
    }
}
