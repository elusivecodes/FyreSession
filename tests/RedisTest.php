<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Session\Handlers\RedisSessionHandler;
use Fyre\Session\Session;
use PHPUnit\Framework\TestCase;

use function getenv;

final class RedisTest extends TestCase
{

    protected RedisSessionHandler $handler;

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

    public function testUpate(): void
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

    protected function setUp(): void
    {
        $this->handler = new RedisSessionHandler([
            'host' => getenv('REDIS_HOST'),
            'password' => getenv('REDIS_PASSWORD'),
            'database' => getenv('REDIS_DATABASE'),
            'port' => getenv('REDIS_PORT')
        ]);

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
