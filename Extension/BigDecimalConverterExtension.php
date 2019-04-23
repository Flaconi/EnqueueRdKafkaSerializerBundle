<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Extension;

use Brick\Math\BigDecimal;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use function Safe\sprintf;

final class BigDecimalConverterExtension implements MessageReceivedExtensionInterface
{
    /** @var array<string> */
    private $convertibleProperties;
    /** @var string */
    private $format;

    /**
     * @param array<string> $convertibleProperties
     */
    public function __construct(array $convertibleProperties, string $format)
    {
        $this->convertibleProperties = $convertibleProperties;
        $this->format                = $format;
    }

    public function onMessageReceived(MessageReceived $context) : void
    {
        $message = $context->getMessage();

        foreach ($this->convertibleProperties as $convertibleProperty) {
            $value = $message->getProperty($convertibleProperty);

            if ($value instanceof BigDecimal || $value === null) {
                continue;
            }

            $message->setProperty($convertibleProperty, BigDecimal::of(sprintf($this->format, $value)));
        }
    }
}
