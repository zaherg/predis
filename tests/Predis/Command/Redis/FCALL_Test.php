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

/**
 * @group commands
 * @group realm-scripting
 * @requiresRedisVersion >= 7.0.0
 */
class FCALL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return FCALL::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'FCALL';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
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
        $actualArguments = ['function', ['arg1', 'arg2', 'arg3', 'arg4']];
        $prefix = 'prefix:';
        $expectedArguments = ['function', 4, 'prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @dataProvider functionsProvider
     * @param  string $function
     * @param  array  $functionArguments
     * @param  mixed  $expectedResponse
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testInvokeGivenFunction(
        string $function,
        array $functionArguments,
        $expectedResponse
    ): void {
        $redis = $this->getClient();
        $redis->executeRaw(['FUNCTION', 'FLUSH']);

        $this->assertSame('mylib', $redis->function->load($function));

        $actualResponse = $redis->fcall(...$functionArguments);
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertEquals('OK', $redis->function->delete('mylib'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testInvokeGivenFunctionResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame('mylib', $redis->function->load(
            "#!lua name=mylib \n redis.register_function('myfunc', function(keys, args) return 'hello' end)")
        );

        $actualResponse = $redis->fcall('myfunc', []);
        $this->assertSame('hello', $actualResponse);
        $this->assertEquals('OK', $redis->function->delete('mylib'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsExceptionOnNonExistingFunctionGiven(): void
    {
        $redis = $this->getClient();
        $redis->executeRaw(['FUNCTION', 'FLUSH']);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR Function not found');

        $redis->fcall('function', []);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['function', []],
                ['function', 0],
            ],
            'with provided keys' => [
                ['function', ['key1', 'key2']],
                ['function', 2, 'key1', 'key2'],
            ],
            'with provided keys and arguments' => [
                ['function', ['key1', 'key2'], 'arg1', 'arg2'],
                ['function', 2, 'key1', 'key2', 'arg1', 'arg2'],
            ],
        ];
    }

    public function functionsProvider(): array
    {
        return [
            'with default arguments' => [
                "#!lua name=mylib \n redis.register_function('myfunc', function(keys, args) return 'hello' end)",
                ['myfunc', []],
                'hello',
            ],
            'with provided keys' => [
                "#!lua name=mylib \n redis.register_function('myfunc', function(keys, args) return keys[1] end)",
                ['myfunc', ['key1']],
                'key1',
            ],
            'with provided keys and arguments' => [
                "#!lua name=mylib \n redis.register_function('myfunc', function(keys, args) return keys[1] .. ' ' .. args[1] end)",
                ['myfunc', ['key1'], 'arg1'],
                'key1 arg1',
            ],
        ];
    }
}
