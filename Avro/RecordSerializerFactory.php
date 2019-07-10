<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Avro;

use AvroIODatumReader;
use AvroIODatumWriter;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry;
use ReflectionProperty;
use const FlixTech\AvroSerializer\Serialize\readDatum;
use const FlixTech\AvroSerializer\Serialize\writeDatum;
use function FlixTech\AvroSerializer\Serialize\avroStringIo;
use function Widmogrod\Functional\curryN;

final class RecordSerializerFactory
{
    public static function get(
        Registry $registry,
        AvroIODatumWriter $datumWriter,
        AvroIODatumReader $datumReader
    ) : RecordSerializer {
        $serializer = new RecordSerializer( $registry);

        $writer = static function () use ($datumWriter) : callable {
            $io     = avroStringIo('');

            return curryN(4, writeDatum)($datumWriter)($io);
        };

        $reader = static function () use ($datumReader) : callable {
            $io     = avroStringIo('');

            return curryN(5, readDatum)($datumReader)($io);
        };

        $setProperty = static function (string $propertyName, callable $factoryFunction) use ($serializer) : void {
            $reflectionProperty = new ReflectionProperty(RecordSerializer::class, $propertyName);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($serializer, $factoryFunction());
        };

        $setProperty('datumWriterFactoryFunc', $writer);
        $setProperty('datumReaderFactoryFunc', $reader);

        return $serializer;
    }
}
