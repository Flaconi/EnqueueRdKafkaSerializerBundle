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

    /**
     * @param array<string> $convertibleProperties
     */
    public function __construct(array $convertibleProperties)
    {
        $this->convertibleProperties = $convertibleProperties;
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
                'Y-m-d H:i:s.u',
                $value['date'],
                new DateTimeZone($value['timezone'])
            );
            $message->setProperty($convertibleProperty, $datetime);
        }
    }
}
