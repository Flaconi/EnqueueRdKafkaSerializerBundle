<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Extension;

use DateTimeImmutable;
use DateTimeZone;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use function is_array;

final class ImmutableDateTimeConverterExtension implements MessageReceivedExtensionInterface
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

    /**
     * @inheritDoc
     */
    public function onMessageReceived(MessageReceived $context) : void
    {
        $message = $context->getMessage();

        foreach ($this->convertibleProperties as $convertibleProperty) {
            $value = $message->getProperty($convertibleProperty);

            if (! is_array($value)) {
                continue;
            }

            $datetime = DateTimeImmutable::createFromFormat(
                $this->format,
                $value['date'],
                new DateTimeZone($value['timezone'])
            );
            $message->setProperty($convertibleProperty, $datetime);
        }
    }
}
