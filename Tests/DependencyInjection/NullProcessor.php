<?php

declare(strict_types=1);

namespace Flaconi\EnqueueRdKafkaSerializerBundle\Tests\DependencyInjection;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class NullProcessor implements Processor
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function process(Message $message, Context $context) : void
    {
    }
}
