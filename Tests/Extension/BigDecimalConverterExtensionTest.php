<?php

declare(strict_types=1);

namespace Flaconi\Tests\EnqueueRdKafkaSerializerBundle\Extension;

use Brick\Math\BigDecimal;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Null\NullMessage;
use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\BigDecimalConverterExtension;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\Extension\BigDecimalConverterExtension
 */
class BigDecimalConverterExtensionTest extends TestCase
{
    public function testOnMessageReceived() : void
    {
        $extension = new BigDecimalConverterExtension(['dummy', 'foobar', 'bar_foo' ], '%.4f');

        $msg = new NullMessage('', ['foobar' => 12.0004, 'dummy' => BigDecimal::of(2)]);

        $context = new MessageReceived(
            $this->prophesize(Context::class)->reveal(),
            $this->prophesize(Consumer::class)->reveal(),
            $msg,
            $this->prophesize(Processor::class)->reveal(),
            0,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $extension->onMessageReceived($context);

        self::assertEquals(BigDecimal::of('12.0004'), $msg->getProperty('foobar'));
        self::assertEquals(BigDecimal::of(2), $msg->getProperty('dummy'));
    }
}
