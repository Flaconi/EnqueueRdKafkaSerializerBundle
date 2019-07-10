<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Avro;

use AvroException;
use AvroField;
use AvroIOBinaryEncoder;
use AvroIODatumWriter;
use AvroIOTypeException;
use AvroRecordSchema;
use AvroSchema;
use AvroSchemaParseException;
use AvroUnionSchema;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use phpseclib\Math\BigInteger;
use function array_key_exists;
use function is_array;
use function pack;

final class IODatumWriter extends AvroIODatumWriter
{
    /**
     * @param AvroSchema          $writers_schema
     * @param mixed               $datum
     * @param AvroIOBinaryEncoder $encoder
     *
     * @return mixed
     *
     * @throws AvroException
     * @throws AvroIOTypeException
     * @throws AvroSchemaParseException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function write_data($writers_schema, $datum, $encoder)
    {
        $datumNew = $this->transformData($writers_schema, $datum);

        return parent::write_data($writers_schema, $datumNew, $encoder);
    }

    /**
     * @param AvroSchema|AvroRecordSchema|AvroUnionSchema $writersSchema
     * @param mixed                                       $datum
     *
     * @return mixed
     */
    private function transformData(AvroSchema $writersSchema, $datum)
    {
        if ($writersSchema instanceof AvroUnionSchema) {
            foreach ($writersSchema->schemas() as $schema) {
                $datum = $this->transformData($schema, $datum);
            }

            return $datum;
        }

        if ($writersSchema instanceof AvroRecordSchema) {
            if (is_array($datum)) {
                /** @var AvroField $field */
                foreach ($writersSchema->fields() as $field) {
                    if (! array_key_exists($field->name(), $datum)) {
                        continue;
                    }

                    $datum[$field->name()] = $this->transformData($field->type(), $datum[$field->name()]);
                }
            }

            return $datum;
        }

        return $this->transformLogicalType($writersSchema, $datum);
    }

    /**
     * @param mixed $datum
     *
     * @return mixed
     */
    private function transformLogicalType(AvroSchema $writersSchema, $datum)
    {
        switch ($writersSchema->logical_type()) {
            case 'decimal':
                if ($datum instanceof BigDecimal) {
                    if ($datum->isZero()) {
                        return pack('Z', $datum->isZero());
                    }

                    $int = new BigInteger($datum->getUnscaledValue()->toInt());
                    $int->setPrecision(24);

                    return $int->toBytes();
                }

                return $datum;
            case 'timestamp-millis':
                if ($datum instanceof DateTimeImmutable) {
                    return $datum->getTimestamp() * 1000;
                }

                return $datum;
            default:
                return $datum;
        }
    }
}
