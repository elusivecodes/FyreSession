<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Session\Handlers\RedisSessionHandler;
use Fyre\Session\Session;
use PHPUnit\Framework\TestCase;

use function getenv;

final class RedisTest extends TestCase
{
    protected RedisSessionHandler $handler;

    protected Session $session;

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
        $container->singleton(Config::class);
        $container->singleton(Session::class);
        $container->use(Config::class)->set('Session', [
            'handler' => [
                'className' => RedisSessionHandler::class,
                'host' => getenv('REDIS_HOST'),
                'password' => getenv('REDIS_PASSWORD'),
                'database' => getenv('REDIS_DATABASE'),
                'port' => getenv('REDIS_PORT'),
            ],
        ]);

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
    }
}
