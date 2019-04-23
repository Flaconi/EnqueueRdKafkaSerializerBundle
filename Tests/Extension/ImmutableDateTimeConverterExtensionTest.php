<?php declare(strict_types=1);

namespace Flaconi\Tests\EnqueueRdKafkaSerializerBundle\Extension;

use DateTimeImmutable;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Null\NullMessage;
use Flaconi\EnqueueRdKafkaSerializerBundle\Extension\ImmutableDateTimeConverterExtension;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use const DATE_ATOM;

/**
 * @covers \Flaconi\EnqueueRdKafkaSerializerBundle\Extension\ImmutableDateTimeConverterExtension
 */
class ImmutableDateTimeConverterExtensionTest extends TestCase
{
    public function testOnMessageReceived() : void
    {
        $extension = new ImmutableDateTimeConverterExtension(['dummy', 'foobar', 'bar_foo'], 'Y-m-d H:i:s.u');

        $msg = new NullMessage(
            '',
            [
                'foobar' => ['date' => '2018-11-20 12:58:16.000000', 'timezone' => '+00:00'],
                'dummy' => new DateTimeImmutable('2018-11-22T12:58:16+00:00'),
            ]
        );

        $context = new MessageReceived(
            $this->prophesize(Context::class)->reveal(),
            $this->prophesize(Consumer::class)->reveal(),
            $msg,
            $this->prophesize(Processor::class)->reveal(),
            0,
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $extension->onMessageReceived($context);

        self::assertEquals('2018-11-20T12:58:16+00:00', $msg->getProperty('foobar')->format(DATE_ATOM));
        self::assertEquals('2018-11-22T12:58:16+00:00', $msg->getProperty('dummy')->format(DATE_ATOM));
    }
}
