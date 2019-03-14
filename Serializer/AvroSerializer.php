<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Serializer;

use AvroSchema;
use Enqueue\Client\Config;
use Enqueue\RdKafka\RdKafkaMessage;
use Exception;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Exception\SchemaRegistryException;
use FlixTech\SchemaRegistryApi\Registry;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * @author Alexander Miehe <alexander.miehe@flaconi.de>
 */
final class AvroSerializer implements ProcessorSerializer
{
    /**
     * @var string
     */
    private $processorName;
    /**
     * @var RecordSerializer
     */
    private $recordSerializer;

    /**
     * @var string
     */
    private $schemaName;
    /**
     * @var Registry
     */
    private $registry;

    public function setProcessorName(string $processorName): void
    {
        $this->processorName = $processorName;
    }

    /**
     * @param RecordSerializer $recordSerializer
     * @param Registry         $registry
     * @param string           $schemaName
     */
    public function __construct(RecordSerializer $recordSerializer, Registry $registry, string $schemaName)
    {
        $this->recordSerializer = $recordSerializer;
        $this->schemaName = $schemaName;
        $this->registry = $registry;
    }

    /**
     * @param RdKafkaMessage $message
     *
     * @return string
     *
     * @throws SchemaRegistryException
     * @throws Exception
     */
    public function toString(RdKafkaMessage $message): string
    {
        $properties = $message->getProperties();

        unset($properties[Config::PROCESSOR]);

        return $this->recordSerializer->encodeRecord(
            $this->schemaName,
            $this->extractValueFromRegistryResponse($this->registry->latestVersion($this->schemaName)),
            $properties
        );
    }

    /**
     * @param string $string
     *
     * @return RdKafkaMessage
     *
     * @throws SchemaRegistryException
     */
    public function toMessage(string $string): RdKafkaMessage
    {
        $message = new RdKafkaMessage('', $this->recordSerializer->decodeMessage($string));

        $message->setProperty(Config::PROCESSOR, $this->processorName);

        return $message;
    }

    /**
     * @param PromiseInterface|\Exception|\Psr\Http\Message\ResponseInterface $response
     *
     * @return AvroSchema
     *
     * @throws \Exception
     */
    private function extractValueFromRegistryResponse($response): AvroSchema
    {
        if ($response instanceof PromiseInterface) {
            $response = $response->wait();
        }

        if ($response instanceof \Exception) {
            throw $response;
        }

        return $response;
    }
}
