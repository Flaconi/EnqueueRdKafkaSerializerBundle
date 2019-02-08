<?php declare(strict_types = 1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\DependencyInjection;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class NullProcessor implements Processor
{
    public function process(Message $message, Context $context)
    {
    }
}
