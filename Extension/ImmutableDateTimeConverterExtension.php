<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Extension;

use DateTimeImmutable;
use DateTimeZone;
use function is_array;

final class ImmutableDateTimeConverterExtension extends ConverterExtension
{
    protected function isConvertible($value): bool
    {
        return is_array($value);
    }

    protected function convert($value)
    {
        return DateTimeImmutable::createFromFormat(
            $this->format,
            $value['date'],
            new DateTimeZone($value['timezone']),
            );
    }


}
