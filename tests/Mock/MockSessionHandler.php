<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Session\SessionHandler;
use SessionHandlerInterface;

/**
 * MockSessionHandler
 */
class MockSessionHandler extends SessionHandler implements SessionHandlerInterface
{

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $sessionId): bool
    {
        return true;
    }

    public function gc(int $expires): int|false
    {
        return 1;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $sessionId): string|false
    {
        return '';
    }

    public function write(string $sessionId, string $data): bool
    {
        return true;
    }

}
