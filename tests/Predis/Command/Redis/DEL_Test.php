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
 * @group realm-key
 */
class DEL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\DEL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'DEL';
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
        $command = $this->getCommand();

        $this->assertSame(10, $command->parseResponse(10));
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
    public function testReturnsNumberOfDeletedKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->del('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->del('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->del('foo', 'hoge'));

        $redis->set('foo', 'bar');
        $redis->set('hoge', 'piyo');
        $this->assertSame(2, $redis->del('foo', 'hoge'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsNumberOfDeletedKeysResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame(0, $redis->del('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->del('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->del('foo', 'hoge'));

        $redis->set('foo', 'bar');
        $redis->set('hoge', 'piyo');
        $this->assertSame(2, $redis->del('foo', 'hoge'));
    }
}
