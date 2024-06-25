<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Session\Session;
use PHPUnit\Framework\TestCase;

use function sleep;

final class SessionTest extends TestCase
{
    public function testConsume(): void
    {
        Session::set('test', 'value');

        $this->assertSame(
            'value',
            Session::consume('test')
        );

        $this->assertFalse(
            Session::has('test')
        );
    }

    public function testGet(): void
    {
        Session::set('test', 'value');

        $this->assertSame(
            'value',
            Session::get('test')
        );
    }

    public function testHas(): void
    {
        Session::set('test', 'value');

        $this->assertTrue(
            Session::has('test')
        );
    }

    public function testId(): void
    {
        $this->assertMatchesRegularExpression(
            '/[a-z0-9]{26}/',
            Session::id()
        );
    }

    public function testIsActive(): void
    {
        $this->assertTrue(
            Session::isActive()
        );
    }

    public function testSetFlash(): void
    {
        Session::setFlash('test', 'value');

        $this->assertTrue(
            Session::has('test')
        );

        Session::rotateFlashData();
        Session::clearFlashData();

        $this->assertFalse(
            Session::has('test')
        );
    }

    public function testSetTemp(): void
    {
        Session::setTemp('test', 'value', 2);

        $this->assertTrue(
            Session::has('test')
        );

        sleep(1);

        Session::clearTempData();

        $this->assertTrue(
            Session::has('test')
        );

        sleep(1);

        Session::clearTempData();

        $this->assertFalse(
            Session::has('test')
        );
    }

    public static function setUpBeforeClass(): void
    {
        Session::clear();
    }
}
