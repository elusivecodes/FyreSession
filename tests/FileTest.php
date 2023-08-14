<?php
declare(strict_types=1);

namespace Tests;

use Fyre\FileSystem\Folder;
use Fyre\Session\Handlers\FileSessionHandler;
use Fyre\Session\Session;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{

    protected FileSessionHandler $handler;

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

        $this->assertTrue(
            (new Folder('sessions'))->isEmpty()
        );

        $this->handler->close();
    }

    protected function setUp(): void
    {
        $this->handler = new FileSessionHandler();

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

    public static function tearDownAfterClass(): void
    {
        (new Folder('sessions'))->delete();
    }

}
