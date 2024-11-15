<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Postgres\PostgresConnection;
use Fyre\DB\TypeParser;
use Fyre\Session\Handlers\Database\PostgresSessionHandler;
use Fyre\Session\Session;
use PHPUnit\Framework\TestCase;

use function getenv;

final class PostgresTest extends TestCase
{
    protected Connection $db;

    protected PostgresSessionHandler $handler;

    protected Session $session;

    public function testGc(): void
    {
        $id = $this->session->id();

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
            $this->db
                ->select()
                ->from('sessions')
                ->execute()
                ->count()
        );
    }

    public function testRead(): void
    {
        $id = $this->session->id();

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
        $id = $this->session->id();

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

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(TypeParser::class);
        $container->singleton(ConnectionManager::class);
        $container->singleton(Config::class);
        $container->singleton(Session::class);
        $container->use(Config::class)->set('Database', [
            'default' => [
                'className' => PostgresConnection::class,
                'host' => getenv('POSTGRES_HOST'),
                'username' => getenv('POSTGRES_USERNAME'),
                'password' => getenv('POSTGRES_PASSWORD'),
                'database' => getenv('POSTGRES_DATABASE'),
                'port' => getenv('POSTGRES_PORT'),
                'charset' => 'utf8',
                'persist' => true,
            ],
        ]);
        $container->use(Config::class)->set('Session', [
            'handler' => [
                'className' => PostgresSessionHandler::class,
            ],
        ]);

        $this->db = $container->use(ConnectionManager::class)->use();

        $this->db->query('DROP TABLE IF EXISTS sessions');

        $this->db->query(<<<'EOT'
            CREATE TABLE sessions (
                id VARCHAR(40) NOT NULL,
                data BYTEA NULL DEFAULT NULL,
                created TIMESTAMP NOT NULL DEFAULT LOCALTIMESTAMP(0),
                modified TIMESTAMP NOT NULL DEFAULT LOCALTIMESTAMP(0),
                PRIMARY KEY (id)
            )
        EOT);

        $this->session = $container->use(Session::class);
        $this->handler = $this->session->getHandler();

        $this->session->start();

        $this->assertTrue(
            $this->handler->open('sessions', '')
        );
    }

    protected function tearDown(): void
    {
        $id = $this->session->id();

        $this->assertTrue(
            $this->handler->destroy($id)
        );

        $this->assertTrue(
            $this->handler->close()
        );

        $this->db->query('DROP TABLE IF EXISTS sessions');
    }
}
