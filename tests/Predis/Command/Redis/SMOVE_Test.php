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
 * @group realm-set
 */
class SMOVE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SMOVE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SMOVE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key:source', 'key:destination', 'member'];
        $expected = ['key:source', 'key:destination', 'member'];

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
        $expectedArguments = ['prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     */
    public function testReturnsMemberExistenceInSet(): void
    {
        $redis = $this->getClient();

        $redis->sadd('letters:source', 'a', 'b', 'c');

        $this->assertSame(1, $redis->smove('letters:source', 'letters:destination', 'b'));
        $this->assertSame(0, $redis->smove('letters:source', 'letters:destination', 'z'));

        $this->assertSameValues(['a', 'c'], $redis->smembers('letters:source'));
        $this->assertSameValues(['b'], $redis->smembers('letters:destination'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsMemberExistenceInSetResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->sadd('letters:source', 'a', 'b', 'c');

        $this->assertSame(1, $redis->smove('letters:source', 'letters:destination', 'b'));
        $this->assertSame(0, $redis->smove('letters:source', 'letters:destination', 'z'));

        $this->assertSameValues(['a', 'c'], $redis->smembers('letters:source'));
        $this->assertSameValues(['b'], $redis->smembers('letters:destination'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongTypeOfSourceKey(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('set:source', 'foo');
        $redis->sadd('set:destination', 'bar');
        $redis->smove('set:destination', 'set:source', 'foo');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongTypeOfDestinationKey(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->sadd('set:source', 'foo');
        $redis->set('set:destination', 'bar');
        $redis->smove('set:destination', 'set:source', 'foo');
    }
}
