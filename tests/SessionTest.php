<?php
declare(strict_types=1);

namespace Tests;

use Closure;
use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Session\Session;
use PHPUnit\Framework\TestCase;

use function sleep;

final class SessionTest extends TestCase
{
    protected Session $session;

    public function testConsume(): void
    {
        $this->session->set('test', 'value');

        $this->assertSame(
            'value',
            $this->session->consume('test')
        );

        $this->assertFalse(
            $this->session->has('test')
        );
    }

    public function testGet(): void
    {
        $this->session->set('test', 'value');

        $this->assertSame(
            'value',
            $this->session->get('test')
        );
    }

    public function testHas(): void
    {
        $this->session->set('test', 'value');

        $this->assertTrue(
            $this->session->has('test')
        );
    }

    public function testId(): void
    {
        $this->assertSame(
            'cli',
            $this->session->id()
        );
    }

    public function testIsActive(): void
    {
        $this->assertFalse(
            $this->session->isActive()
        );
    }

    public function testSetFlash(): void
    {
        $this->session->setFlash('test', 'value');

        $this->assertTrue(
            $this->session->has('test')
        );

        Closure::bind(function(): void {
            $this->rotateFlashData();
            $this->clearTempData();
        }, $this->session, $this->session)();

        $this->assertFalse(
            $this->session->has('test')
        );
    }

    public function testSetTemp(): void
    {
        $this->session->setTemp('test', 'value', 2);

        $this->assertTrue(
            $this->session->has('test')
        );

        sleep(1);

        Closure::bind(function(): void {
            $this->clearTempData();
        }, $this->session, $this->session)();

        $this->assertTrue(
            $this->session->has('test')
        );

        sleep(1);

        Closure::bind(function(): void {
            $this->clearTempData();
        }, $this->session, $this->session)();

        $this->assertFalse(
            $this->session->has('test')
        );
    }

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(Config::class);
        $container->singleton(Session::class);

        $this->session = $container->use(Session::class);

        $this->session->start();
    }
}
