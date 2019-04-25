<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Extension;

use Brick\Math\BigDecimal;
use function Safe\sprintf;

final class BigDecimalConverterExtension extends ConverterExtension
{
    protected function isConvertible($value): bool
    {
        return ! ($value instanceof BigDecimal || $value === null);
    }

    protected function convert($value)
    {
        return BigDecimal::of(sprintf($this->format, $value));
    }
}
