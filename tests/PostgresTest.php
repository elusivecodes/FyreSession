<?php
declare(strict_types=1);

namespace Tests;

use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Postgres\PostgresConnection;
use Fyre\Session\Handlers\Database\PostgresSessionHandler;
use Fyre\Session\Session;
use PHPUnit\Framework\TestCase;

use function getenv;

final class PostgresTest extends TestCase
{
    protected PostgresSessionHandler $handler;

    public function testGc(): void
    {
        $id = Session::id();

        $this->assertSame(
            '',
            $this->handler->read($id)
        );

        $this->assertTrue(
            $this->handler->write($id, 'data1')
        );

        $this->assertSame(
            1,
            $this->handler->gc(-1)
        );

        $this->assertSame(
            0,
            ConnectionManager::use()
                ->select()
                ->from('sessions')
                ->execute()
                ->count()
        );
    }

    public function testRead(): void
    {
        $id = Session::id();

        $this->assertSame(
            '',
            $this->handler->read($id)
        );

        $this->assertTrue(
            $this->handler->write($id, 'data')
        );

        $this->assertSame(
            'data',
            $this->handler->read($id)
        );
    }

    public function testUpdate(): void
    {
        $id = Session::id();

        $this->assertSame(
            '',
            $this->handler->read($id)
        );

        $this->assertTrue(
            $this->handler->write($id, 'data1')
        );

        $this->assertSame(
            'data1',
            $this->handler->read($id)
        );

        $this->assertTrue(
            $this->handler->write($id, 'data2')
        );

        $this->assertSame(
            'data2',
            $this->handler->read($id)
        );
    }

    public static function setUpBeforeClass(): void
    {
        ConnectionManager::clear();

        ConnectionManager::setConfig('default', [
            'className' => PostgresConnection::class,
            'host' => getenv('POSTGRES_HOST'),
            'username' => getenv('POSTGRES_USERNAME'),
            'password' => getenv('POSTGRES_PASSWORD'),
            'database' => getenv('POSTGRES_DATABASE'),
            'port' => getenv('POSTGRES_PORT'),
            'charset' => 'utf8',
            'persist' => true,
        ]);

        $connection = ConnectionManager::use();

        $connection->query('DROP TABLE IF EXISTS sessions');

        $connection->query(<<<'EOT'
            CREATE TABLE sessions (
                id VARCHAR(40) NOT NULL,
                data BYTEA NULL DEFAULT NULL,
                created TIMESTAMP NOT NULL DEFAULT LOCALTIMESTAMP(0),
                modified TIMESTAMP NOT NULL DEFAULT LOCALTIMESTAMP(0),
                PRIMARY KEY (id)
            )
        EOT);
    }

    public static function tearDownAfterClass(): void
    {
        $connection = ConnectionManager::use();
        $connection->query('DROP TABLE IF EXISTS sessions');
    }

    protected function setUp(): void
    {
        $this->handler = new PostgresSessionHandler();

        $this->assertTrue(
            $this->handler->open('sessions', '')
        );
    }

    protected function tearDown(): void
    {
        $id = Session::id();

        $this->assertTrue(
            $this->handler->destroy($id)
        );

        $this->assertTrue(
            $this->handler->close()
        );
    }
}
