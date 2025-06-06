<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;
use Predis\Consumer\Push\PushResponse;

/**
 * @group commands
 * @group realm-pubsub
 */
class UNSUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\UNSUBSCRIBE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'UNSUBSCRIBE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['channel1', 'channel2', 'channel3'];
        $expected = ['channel1', 'channel2', 'channel3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = [['channel1', 'channel2', 'channel3']];
        $expected = ['channel1', 'channel2', 'channel3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['unsubscribe', 'channel', 1];
        $expected = ['unsubscribe', 'channel', 1];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 'arg2', 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 2.0.0
     */
    public function testDoesNotSwitchToSubscribeMode(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['unsubscribe', 'channel', 0], $redis->unsubscribe('channel'));
        $this->assertSame('echoed', $redis->echo('echoed'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 6.0.0
     */
    public function testDoesNotSwitchToSubscribeModeResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = new PushResponse(['unsubscribe', 'channel', 0]);

        $this->assertEquals($expectedResponse, $redis->unsubscribe('channel'));
        $this->assertSame('echoed', $redis->echo('echoed'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 2.0.0
     */
    public function testUnsubscribesFromNotSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['unsubscribe', 'channel', 0], $redis->unsubscribe('channel'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 2.0.0
     */
    public function testUnsubscribesFromSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel', 1], $redis->subscribe('channel'));
        $this->assertSame(['unsubscribe', 'channel', 0], $redis->unsubscribe('channel'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 2.0.0
     */
    public function testUnsubscribesFromAllSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['subscribe', 'channel:foo', 1], $redis->subscribe('channel:foo'));
        $this->assertSame(['subscribe', 'channel:bar', 2], $redis->subscribe('channel:bar'));

        [$_, $unsubscribed1, $_] = $redis->unsubscribe();
        [$_, $unsubscribed2, $_] = $redis->getConnection()->read();
        $this->assertSameValues(['channel:foo', 'channel:bar'], [$unsubscribed1, $unsubscribed2]);

        $this->assertSame('echoed', $redis->echo('echoed'));
    }
}
