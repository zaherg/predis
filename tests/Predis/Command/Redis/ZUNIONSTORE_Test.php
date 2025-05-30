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
use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-zset
 */
class ZUNIONSTORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZUNIONSTORE::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZUNIONSTORE';
    }

    /**
     * @dataProvider argumentsProvider
     * @group disconnected
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
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
        $actualArguments = ['dest', ['arg1', 'arg2', 'arg3', 'arg4']];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:dest', 4, 'prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @dataProvider sortedSetsProvider
     * @param  array  $firstSortedSet
     * @param  array  $secondSortedSet
     * @param  string $destination
     * @param  array  $weights
     * @param  string $aggregate
     * @param  int    $expectedResponse
     * @param  array  $expectedResultSortedSet
     * @return void
     * @requiresRedisVersion >= 2.0.0
     */
    public function testStoresUnionValuesOnSortedSets(
        array $firstSortedSet,
        array $secondSortedSet,
        string $destination,
        array $weights,
        string $aggregate,
        int $expectedResponse,
        array $expectedResultSortedSet
    ): void {
        $redis = $this->getClient();

        $redis->zadd('test-zunionstore1', ...$firstSortedSet);
        $redis->zadd('test-zunionstore2', ...$secondSortedSet);

        $actualResponse = $redis->zunionstore(
            $destination,
            ['test-zunionstore1', 'test-zunionstore2'],
            $weights,
            $aggregate
        );

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertEquals(
            $expectedResultSortedSet,
            $redis->zrange($destination, 0, -1, ['withscores' => true])
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.0.0
     */
    public function testStoresUnionValuesOnSortedSetsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->zadd('test-zunionstore1', 1, 'member1', 2, 'member2', 3, 'member3');
        $redis->zadd('test-zunionstore2', 1, 'member1', 2, 'member2');

        $actualResponse = $redis->zunionstore('destination', ['test-zunionstore1', 'test-zunionstore2']);

        $this->assertSame(3, $actualResponse);
        $this->assertSame(
            [['member1' => 2.0], ['member3' => 3.0], ['member2' => 4.0]],
            $redis->zrange('destination', 0, -1, ['withscores' => true])
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zunionstore('zset_unionstore:destination', ['foo']);
    }

    /**
     * @dataProvider unexpectedValueProvider
     * @param  string $destination
     * @param         $keys
     * @param         $weights
     * @param  string $aggregate
     * @param  string $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        string $destination,
        $keys,
        $weights,
        string $aggregate,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->zunionstore($destination, $keys, $weights, $aggregate);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments only' => [
                ['destination', ['key1', 'key2']],
                ['destination', 2, 'key1', 'key2'],
            ],
            'with weights' => [
                ['destination', ['key1', 'key2'], [1, 2]],
                ['destination', 2, 'key1', 'key2', 'WEIGHTS', 1, 2],
            ],
            'with aggregate' => [
                ['destination', ['key1', 'key2'], [], 'min'],
                ['destination', 2, 'key1', 'key2', 'AGGREGATE', 'MIN'],
            ],
            'with all arguments' => [
                ['destination', ['key1', 'key2'], [1, 2], 'min'],
                ['destination', 2, 'key1', 'key2', 'WEIGHTS', 1, 2, 'AGGREGATE', 'MIN'],
            ],
            'with options array' => [
                ['destination', ['key1', 'key2'], [
                    'weights' => [1, 2],
                    'aggregate' => 'min',
                ]],
                ['destination', 2, 'key1', 'key2', 'WEIGHTS', 1, 2, 'AGGREGATE', 'MIN'],
            ],
        ];
    }

    public function sortedSetsProvider(): array
    {
        return [
            'with required arguments' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2'],
                'destination',
                [],
                'sum',
                3,
                ['member1' => '2', 'member3' => '3', 'member2' => '4'],
            ],
            'with weights' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2'],
                'destination',
                [2, 3],
                'sum',
                3,
                ['member1' => '5', 'member3' => '6', 'member2' => '10'],
            ],
            'with aggregate' => [
                [1, 'member1', 4, 'member2', 3, 'member3'],
                [2, 'member1', 2, 'member2'],
                'destination',
                [],
                'max',
                3,
                ['member1' => '2', 'member3' => '3', 'member2' => '4'],
            ],
            'with all arguments' => [
                [1, 'member1', 5, 'member2', 4, 'member3'],
                [2, 'member1', 2, 'member2'],
                'destination',
                [2, 3],
                'max',
                3,
                ['member1' => '6', 'member3' => '8', 'member2' => '10'],
            ],
        ];
    }

    public function unexpectedValueProvider(): array
    {
        return [
            'with unexpected keys argument' => [
                'destination',
                1,
                [],
                'sum',
                'Wrong keys argument type or position offset',
            ],
            'with unexpected weights argument' => [
                'destination',
                ['key1'],
                1,
                'sum',
                'Wrong weights argument type',
            ],
            'with unexpected aggregate argument' => [
                'destination',
                ['key1'],
                [],
                'wrong',
                'Aggregate argument accepts only: min, max, sum values',
            ],
        ];
    }
}
