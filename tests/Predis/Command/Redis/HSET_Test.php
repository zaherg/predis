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
 * @group realm-hash
 */
class HSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\HSET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 'field', 'value'];
        $expected = ['key', 'field', 'value'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
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
     * @requiresRedisVersion >= 2.0.0
     */
    public function testSetsValueOfSpecifiedField(): void
    {
        $redis = $this->getClient();

        $this->assertSame(1, $redis->hset('metavars', 'foo', 'bar'));
        $this->assertSame(1, $redis->hset('metavars', 'hoge', 'piyo'));

        $this->assertSame(['bar', 'piyo'], $redis->hmget('metavars', 'foo', 'hoge'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testSetsValueOfSpecifiedFieldResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame(1, $redis->hset('metavars', 'foo', 'bar'));
        $this->assertSame(1, $redis->hset('metavars', 'hoge', 'piyo'));

        $this->assertSame(['bar', 'piyo'], $redis->hmget('metavars', 'foo', 'hoge'));
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.0.0
     */
    public function testSetsValueOfSpecifiedFieldUsingCluster(): void
    {
        $redis = $this->getClient();

        $redis->del('metavars');

        $this->testSetsValueOfSpecifiedField();
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/.*Operation against a key holding the wrong kind of value.*/');

        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->hset('metavars', 'foo', 'bar');
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 6.0.0
     */
    public function testThrowsExceptionOnWrongTypeUsingCluster(): void
    {
        $this->testThrowsExceptionOnWrongType();
    }
}
