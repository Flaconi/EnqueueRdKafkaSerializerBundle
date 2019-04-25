<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Extension;

use Brick\Math\BigDecimal;
use function Safe\sprintf;

final class BigDecimalConverterExtension extends ConverterExtension
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    protected function isConvertible($value) : bool
    {
        return ! ($value instanceof BigDecimal || $value === null);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    protected function convert($value) : BigDecimal
    {
        return BigDecimal::of(sprintf($this->format, $value));
    }
}
