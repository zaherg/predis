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
 * @group realm-string
 */
class MGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MGET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MGET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key1', 'key2', 'key3'];
        $expected = ['key1', 'key2', 'key3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = [['key1', 'key2', 'key3']];
        $expected = ['key1', 'key2', 'key3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['value1', 'value2', 'value3'];
        $expected = ['value1', 'value2', 'value3'];

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
     */
    public function testReturnsArrayOfValues(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->set('hoge', 'piyo');

        $this->assertSame(['bar', 'piyo'], $redis->mget('foo', 'hoge'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsArrayOfValuesResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->set('foo', 'bar');
        $redis->set('hoge', 'piyo');

        $this->assertSame(['bar', 'piyo'], $redis->mget('foo', 'hoge'));
    }

    /**
     * @group connected
     */
    public function testReturnsArrayWithNullValuesOnNonExistingKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame([null, null], $redis->mget('foo', 'hoge'));
    }

    /**
     * @group connected
     */
    public function testDoesNotThrowExceptionOnWrongType(): void
    {
        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');
        $this->assertSame([null], $redis->mget('metavars'));
    }
}
