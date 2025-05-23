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

namespace Predis\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use PredisTestCase;

/**
 * @group realm-connection
 */
abstract class PredisConnectionTestCase extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorDoesNotOpenConnection(): void
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeTCP(): void
    {
        $connection = $this->createConnectionWithParams(['scheme' => 'tcp']);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeRedis(): void
    {
        $connection = $this->createConnectionWithParams(['scheme' => 'redis']);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeTls(): void
    {
        $connection = $this->createConnectionWithParams(['scheme' => 'tls']);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeRediss(): void
    {
        $connection = $this->createConnectionWithParams(['scheme' => 'rediss']);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeUnix(): void
    {
        $connection = $this->createConnectionWithParams(['scheme' => 'unix']);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testExposesParameters(): void
    {
        $parameters = $this->getParameters();
        $connection = $this->createConnectionWithParams($parameters);

        $this->assertSame($parameters, $connection->getParameters());
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized(): void
    {
        $parameters = $this->getParameters([
            'alias' => 'redis',
            'read_write_timeout' => 10,
        ]);

        $connection = $this->createConnectionWithParams($parameters);
        $unserialized = unserialize(serialize($connection));

        $this->assertInstanceOf($this->getConnectionClass(), $unserialized);
        $this->assertEquals($parameters, $unserialized->getParameters());
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testAcceptsTcpNodelayParameter(): void
    {
        $connection = $this->createConnectionWithParams(['tcp_nodelay' => false]);
        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection = $this->createConnectionWithParams(['tcp_nodelay' => true]);
        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testConnectForcesConnection(): void
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testDoesNotThrowExceptionOnConnectWhenAlreadyConnected(): void
    {
        $connection = $this->createConnection();

        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testDisconnectForcesDisconnection(): void
    {
        $connection = $this->createConnection();

        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testDoesNotThrowExceptionOnDisconnectWhenAlreadyDisconnected(): void
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testGetResourceForcesConnection(): void
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
        $connection->connect();

        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testSendingCommandForcesConnection(): void
    {
        $connection = $this->createConnection();
        $commands = $this->getCommandFactory();

        $cmdPing = $commands->create('ping');

        $this->assertEquals('PONG', $connection->executeCommand($cmdPing));
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testExecutesCommandOnServer(): void
    {
        $commands = $this->getCommandFactory();

        /** @var CommandInterface|MockObject */
        $cmdPing = $this->getMockBuilder($commands->getCommandClass('ping'))
            ->onlyMethods(['parseResponse'])
            ->getMock();
        $cmdPing->expects($this->never())
            ->method('parseResponse');

        $connection = $this->createConnection();

        $this->assertEquals('PONG', $connection->executeCommand($cmdPing));
    }

    /**
     * @group connected
     */
    public function testExecutesCommandWithHolesInArguments(): void
    {
        $commands = $this->getCommandFactory();
        $cmdDel = $commands->create('mget', [0 => 'key:0', 2 => 'key:2']);

        $connection = $this->createConnection();

        $this->assertSame([null, null], $connection->executeCommand($cmdDel));
    }

    /**
     * @group connected
     */
    public function testExecutesMultipleCommandsOnServer(): void
    {
        $commands = $this->getCommandFactory();

        $cmdPing = $commands->create('ping');
        $cmdEcho = $commands->create('echo', ['echoed']);
        $cmdGet = $commands->create('get', ['foobar']);
        $cmdRpush = $commands->create('rpush', ['metavars', 'foo', 'hoge', 'lol']);
        $cmdLrange = $commands->create('lrange', ['metavars', 0, -1]);

        $connection = $this->createConnection(true);

        $this->assertEquals('PONG', $connection->executeCommand($cmdPing));
        $this->assertSame('echoed', $connection->executeCommand($cmdEcho));
        $this->assertNull($connection->executeCommand($cmdGet));
        $this->assertSame(3, $connection->executeCommand($cmdRpush));
        $this->assertSame(['foo', 'hoge', 'lol'], $connection->executeCommand($cmdLrange));
    }

    /**
     * @group connected
     */
    public function testWritesCommandToServer(): void
    {
        $commands = $this->getCommandFactory();

        /** @var CommandInterface|MockObject */
        $cmdEcho = $this->getMockBuilder($commands->getCommandClass('echo'))
            ->onlyMethods(['parseResponse'])
            ->getMock();
        $cmdEcho->setArguments(['ECHOED']);
        $cmdEcho
            ->expects($this->never())
            ->method('parseResponse');

        $connection = $this->createConnection();
        $connection->writeRequest($cmdEcho);
        $connection->disconnect();
    }

    /**
     * @group connected
     */
    public function testReadsCommandFromServer(): void
    {
        $commands = $this->getCommandFactory();

        /** @var CommandInterface|MockObject */
        $cmdEcho = $this->getMockBuilder($commands->getCommandClass('echo'))
            ->onlyMethods(['parseResponse'])
            ->getMock();
        $cmdEcho->setArguments(['ECHOED']);
        $cmdEcho
            ->expects($this->never())
            ->method('parseResponse');

        $connection = $this->createConnection();
        $connection->writeRequest($cmdEcho);

        $this->assertSame('ECHOED', $connection->readResponse($cmdEcho));
    }

    /**
     * @group connected
     */
    public function testIsAbleToWriteMultipleCommandsAndReadThemBackForPipelining(): void
    {
        $commands = $this->getCommandFactory();

        /** @var CommandInterface|MockObject */
        $cmdPing = $this->getMockBuilder($commands->getCommandClass('ping'))
            ->onlyMethods(['parseResponse'])
            ->getMock();
        $cmdPing
            ->expects($this->never())
            ->method('parseResponse');

        /** @var CommandInterface|MockObject */
        $cmdEcho = $this->getMockBuilder($commands->getCommandClass('echo'))
            ->onlyMethods(['parseResponse'])
            ->getMock();
        $cmdEcho->setArguments(['ECHOED']);
        $cmdEcho
            ->expects($this->never())
            ->method('parseResponse');

        $connection = $this->createConnection();

        $connection->writeRequest($cmdPing);
        $connection->writeRequest($cmdEcho);

        $this->assertEquals('PONG', $connection->readResponse($cmdPing));
        $this->assertSame('ECHOED', $connection->readResponse($cmdEcho));
    }

    /**
     * @group connected
     */
    public function testSendsInitializationCommandsOnConnection(): void
    {
        $commands = $this->getCommandFactory();

        /** @var CommandInterface|MockObject */
        $cmdPing = $this->getMockBuilder($commands->getCommandClass('ping'))
            ->onlyMethods(['getArguments'])
            ->getMock();
        $cmdPing
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn([]);

        /** @var CommandInterface|MockObject */
        $cmdEcho = $this->getMockBuilder($commands->getCommandClass('echo'))
            ->onlyMethods(['getArguments'])
            ->getMock();
        $cmdEcho->setArguments(['ECHOED']);
        $cmdEcho
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn(['ECHOED']);

        $connection = $this->createConnection();
        $connection->addConnectCommand($cmdPing);
        $connection->addConnectCommand($cmdEcho);

        $connection->connect();
    }

    /**
     * @group connected
     */
    public function testReadsStatusResponses(): void
    {
        $commands = $this->getCommandFactory();
        $connection = $this->createConnection(true);

        $connection->writeRequest($commands->create('set', ['foo', 'bar']));
        $this->assertInstanceOf('Predis\Response\Status', $connection->read());

        $connection->writeRequest($commands->create('ping'));
        $this->assertInstanceOf('Predis\Response\Status', $connection->read());

        $connection->writeRequest($commands->create('multi'));
        $connection->writeRequest($commands->create('ping'));
        $this->assertInstanceOf('Predis\Response\Status', $connection->read());
        $this->assertInstanceOf('Predis\Response\Status', $connection->read());
    }

    /**
     * @group connected
     */
    public function testReadsBulkResponses(): void
    {
        $commands = $this->getCommandFactory();
        $connection = $this->createConnection(true);

        $connection->executeCommand($commands->create('set', ['foo', 'bar']));

        $connection->writeRequest($commands->create('get', ['foo']));
        $this->assertSame('bar', $connection->read());

        $connection->writeRequest($commands->create('get', ['hoge']));
        $this->assertNull($connection->read());
    }

    /**
     * @group connected
     */
    public function testReadsIntegerResponses(): void
    {
        $commands = $this->getCommandFactory();
        $connection = $this->createConnection(true);

        $connection->executeCommand($commands->create('rpush', ['metavars', 'foo', 'hoge', 'lol']));
        $connection->writeRequest($commands->create('llen', ['metavars']));

        $this->assertSame(3, $connection->read());
    }

    /**
     * @group connected
     */
    public function testReadsErrorResponsesAsResponseErrorObjects(): void
    {
        $commands = $this->getCommandFactory();
        $connection = $this->createConnection(true);

        $connection->executeCommand($commands->create('set', ['foo', 'bar']));
        $connection->writeRequest($commands->create('rpush', ['foo', 'baz']));

        $this->assertInstanceOf('Predis\Response\Error', $error = $connection->read());
        $this->assertMatchesRegularExpression(
            '/[ERR|WRONGTYPE] Operation against a key holding the wrong kind of value/', $error->getMessage()
        );
    }

    /**
     * @group connected
     */
    public function testReadsMultibulkResponsesAsArrays(): void
    {
        $commands = $this->getCommandFactory();
        $connection = $this->createConnection(true);

        $connection->executeCommand($commands->create('rpush', ['metavars', 'foo', 'hoge', 'lol']));
        $connection->writeRequest($commands->create('lrange', ['metavars', 0, -1]));

        $this->assertSame(['foo', 'hoge', 'lol'], $connection->read());
    }

    /**
     * @group connected
     * @group slow
     */
    public function testThrowsExceptionOnReadWriteTimeout(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');

        $commands = $this->getCommandFactory();

        $connection = $this->createConnectionWithParams([
            'read_write_timeout' => 0.5,
        ], true);

        $connection->executeCommand($commands->create('brpop', ['foo', 3]));
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns the fully-qualified class name of the connection used for tests.
     *
     * @return string
     */
    abstract protected function getConnectionClass(): string;

    /**
     * Returns a named array with the default connection parameters and their values.
     *
     * @return array Default connection parameters.
     */
    protected function getDefaultParametersArray(): array
    {
        return [
            'scheme' => 'tcp',
            'host' => constant('REDIS_SERVER_HOST'),
            'port' => constant('REDIS_SERVER_PORT'),
            'database' => constant('REDIS_SERVER_DBNUM'),
            'password' => getenv('REDIS_PASSWORD') ?: constant('REDIS_PASSWORD'),
            'read_write_timeout' => 2,
        ];
    }

    /**
     * Asserts the connection is using a persistent resource stream.
     *
     * This assertion will trigger a connect() operation if the connection has
     * not been open yet.
     *
     * @param resource $resource
     */
    protected function assertPersistentConnection($resource): void
    {
        $this->assertSame('persistent stream', get_resource_type($resource));
    }

    /**
     * Asserts the connection is not using a persistent resource stream.
     *
     * This assertion will trigger a connect() operation if the connection has
     * not been open yet.
     *
     * @param resource $resource
     */
    protected function assertNonPersistentConnection($resource): void
    {
        $this->assertSame('stream', get_resource_type($resource));
    }

    /**
     * Creates a new connection instance.
     *
     * @param bool $initialize Push default initialization commands (SELECT and FLUSHDB)
     *
     * @return NodeConnectionInterface
     */
    protected function createConnection(bool $initialize = false, bool $noAuth = false): NodeConnectionInterface
    {
        return $this->createConnectionWithParams([], $initialize, $noAuth);
    }

    /**
     * Creates a new connection instance using additional connection parameters.
     *
     * @param string|array|ParametersInterface $parameters Additional connection parameters
     * @param bool                             $initialize Push default initialization commands (SELECT and FLUSHDB)
     *
     * @return NodeConnectionInterface
     */
    protected function createConnectionWithParams(
        $parameters,
        $initialize = false,
        bool $noAuth = false
    ): NodeConnectionInterface {
        $class = $this->getConnectionClass();
        $commands = $this->getCommandFactory();

        if (!$parameters instanceof ParametersInterface) {
            $parameters = $this->getParameters($parameters);
        }

        $connection = new $class($parameters);

        if (!$noAuth) {
            if (isset($parameters->password) && strlen($parameters->password)) {
                if (!isset($parameters->username) || !strlen($parameters->username)) {
                    $parameters->username = 'default';
                }

                $connection->addConnectCommand(
                    new RawCommand(
                        'HELLO',
                        [$parameters->protocol, 'AUTH', $parameters->username, $parameters->password]
                    )
                );
            }
        }

        if ($initialize) {
            $connection->addConnectCommand(
                $commands->create('select', [$parameters->database])
            );

            $connection->addConnectCommand(
                $commands->create('flushdb')
            );
        }

        return $connection;
    }
}
