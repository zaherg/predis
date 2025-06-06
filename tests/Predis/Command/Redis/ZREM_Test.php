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

/**
 * @group commands
 * @group realm-zset
 */
class ZREM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZREM';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZREM';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['zset', 'member1', 'member2', 'member3'];
        $expected = ['zset', 'member1', 'member2', 'member3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
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
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     */
    public function testRemovesSpecifiedMembers(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(3, $redis->zrem('letters', 'b', 'd', 'f', 'z'));
        $this->assertSame(['a', 'c', 'e'], $redis->zrange('letters', 0, -1));

        $this->assertSame(0, $redis->zrem('unknown', 'a'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testRemovesSpecifiedMembersResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(3, $redis->zrem('letters', 'b', 'd', 'f', 'z'));
        $this->assertSame(['a', 'c', 'e'], $redis->zrange('letters', 0, -1));

        $this->assertSame(0, $redis->zrem('unknown', 'a'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zrem('foo', 'bar');
    }
}
