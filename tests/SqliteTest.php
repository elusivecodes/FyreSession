<?php
declare(strict_types=1);

namespace Tests;

use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Sqlite\SqliteConnection;
use Fyre\Session\Handlers\DatabaseSessionHandler;
use Fyre\Session\Session;
use PHPUnit\Framework\TestCase;

final class SqliteTest extends TestCase
{
    protected DatabaseSessionHandler $handler;

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
            'className' => SqliteConnection::class,
            'persist' => true,
        ]);

        $connection = ConnectionManager::use();

        $connection->query('DROP TABLE IF EXISTS sessions');

        $connection->query(<<<'EOT'
            CREATE TABLE sessions (
                id VARCHAR(40) NOT NULL,
                data BLOB NULL DEFAULT NULL,
                created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
        $this->handler = new DatabaseSessionHandler();

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
