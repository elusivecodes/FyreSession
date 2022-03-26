<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\DB\ConnectionManager,
    Fyre\DB\Handlers\MySQL\MySQLConnection,
    Fyre\Session\Handlers\DatabaseSessionHandler,
    Fyre\Session\Session,
    Fyre\Session\SessionHandler,
    PHPUnit\Framework\TestCase;

use function
    getenv;

final class DatabaseTest extends TestCase
{

    protected DatabaseSessionHandler $handler;

    public function testRead()
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

    public function testUpate()
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

    public function testGc()
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
                ->builder()
                ->select()
                ->table('sessions')
                ->execute()
                ->count()
        );
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

    public static function setUpBeforeClass(): void
    {
        ConnectionManager::clear();

        ConnectionManager::setConfig('default', [
            'className' => MySQLConnection::class,
            'host' => getenv('DB_HOST'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'database' => getenv('DB_NAME'),
            'port' => getenv('DB_PORT'),
            'collation' => 'utf8mb4_unicode_ci',
            'charset' => 'utf8mb4',
            'compress' => true,
            'persist' => true
        ]);

        $connection = ConnectionManager::use();

        $connection->query('DROP TABLE IF EXISTS `sessions`');

        $connection->query(<<<EOT
            CREATE TABLE `sessions` (
                `id` VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                `data` BLOB NULL DEFAULT NULL,
                `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `modified` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);
    }

    public static function tearDownAfterClass(): void
    {
        $connection = ConnectionManager::use();
        $connection->query('DROP TABLE IF EXISTS `sessions`');
    }

}
