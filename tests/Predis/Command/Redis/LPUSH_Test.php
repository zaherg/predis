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
 * @group realm-list
 */
class LPUSH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LPUSH';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LPUSH';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 'value1', 'value2', 'value3'];
        $expected = ['key', 'value1', 'value2', 'value3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsValuesAsSingleArray(): void
    {
        $arguments = ['key', ['value1', 'value2', 'value3']];
        $expected = ['key', 'value1', 'value2', 'value3'];

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
    public function testPushesElementsToHeadOfList(): void
    {
        $redis = $this->getClient();

        // NOTE: List push operations return the list length since Redis commit 520b5a3
        $this->assertSame(1, $redis->lpush('metavars', 'foo'));
        $this->assertSame(2, $redis->lpush('metavars', 'hoge'));
        $this->assertSame(['hoge', 'foo'], $redis->lrange('metavars', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testPushesElementsToHeadOfListResp3(): void
    {
        $redis = $this->getResp3Client();

        // NOTE: List push operations return the list length since Redis commit 520b5a3
        $this->assertSame(1, $redis->lpush('metavars', 'foo'));
        $this->assertSame(2, $redis->lpush('metavars', 'hoge'));
        $this->assertSame(['hoge', 'foo'], $redis->lrange('metavars', 0, -1));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->lpush('metavars', 'hoge');
    }
}
