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
class ZINCRBY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZINCRBY';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZINCRBY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 1.0, 'member'];
        $expected = ['key', 1.0, 'member'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('1', $this->getCommand()->parseResponse('1'));
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
    public function testIncrementsScoreOfMemberByFloat(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('1', $redis->zincrby('letters', 1, 'member'));
        $this->assertEquals('0', $redis->zincrby('letters', -1, 'member'));
        $this->assertEquals('0.5', $redis->zincrby('letters', 0.5, 'member'));
        $this->assertEquals('-10', $redis->zincrby('letters', -10.5, 'member'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testIncrementsScoreOfMemberByFloatResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame(1.0, $redis->zincrby('letters', 1, 'member'));
        $this->assertSame(0.0, $redis->zincrby('letters', -1, 'member'));
        $this->assertSame(0.5, $redis->zincrby('letters', 0.5, 'member'));
        $this->assertSame(-10.0, $redis->zincrby('letters', -10.5, 'member'));
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
        $redis->zincrby('foo', 1, 'bar');
    }
}
