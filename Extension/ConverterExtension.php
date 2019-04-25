<?php declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Extension;

use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class ConverterExtension implements MessageReceivedExtensionInterface
{
    /** @var array<string> */
    private $convertibleProperties;
    /** @var string */
    protected $format;
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param array<string> $convertibleProperties
     */
    public function __construct(array $convertibleProperties, string $format, PropertyAccessor $propertyAccessor)
    {
        $this->convertibleProperties = $convertibleProperties;
        $this->format                = $format;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function onMessageReceived(MessageReceived $context) : void
    {
        $message = $context->getMessage();

        $properties = $message->getProperties();

        foreach ($this->convertibleProperties as $convertibleProperty) {
            $value = $this->propertyAccessor->getValue($properties, $convertibleProperty);

            if (! $this->isConvertible($value)) {
                continue;
            }

            $this->propertyAccessor->setValue($properties, $convertibleProperty, $this->convert($value));

            $message->setProperties($properties);
        }
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    abstract protected function isConvertible($value): bool;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    abstract protected function convert($value);
}