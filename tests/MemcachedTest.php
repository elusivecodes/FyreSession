<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\Session\Handlers\MemcachedSessionHandler,
    Fyre\Session\Session,
    PHPUnit\Framework\TestCase;

use function
    getenv;

final class MemcachedTest extends TestCase
{

    protected MemcachedSessionHandler $handler;

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

    protected function setUp(): void
    {
        $this->handler = new MemcachedSessionHandler([
            'host' => getenv('MEMCACHED_HOST'),
            'port' => getenv('MEMCACHED_PORT')
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
