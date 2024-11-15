<?php
declare(strict_types=1);

namespace Fyre\Session;

use SessionHandlerInterface;

use function array_replace_recursive;

/**
 * SessionHandler
 */
abstract class SessionHandler implements SessionHandlerInterface
{
    protected static array $defaults = [
        'prefix' => '',
        'expires' => 3600,
    ];

    protected array $config = [];

    protected Session $session;

    protected string|null $sessionId = null;

    /**
     * New SessionHandler constructor.
     *
     * @param Session $session The Session.
     * @param array $options Options for the handler.
     */
    public function __construct(Session $session, array $options = [])
    {
        $this->session = $session;

        $this->config = array_replace_recursive(self::$defaults, static::$defaults, $options);
    }

    /**
     * Close the session.
     *
     * @return bool TRUE if the session was closed, otherwise FALSE.
     */
    abstract public function close(): bool;

    /**
     * Destroy the session.
     *
     * @return bool TRUE if the session was destroyed, otherwise FALSE.
     */
    abstract public function destroy(string $sessionId): bool;

    /**
     * Session garbage collector.
     *
     * @param int $expires The maximum session lifetime.
     * @return false|int The number of sessions removed.
     */
    abstract public function gc(int $expires): false|int;

    /**
     * Open the session.
     *
     * @param string $path The session path.
     * @param string $name The session name.
     * @return bool TRUE if the session was opened, otherwise FALSE.
     */
    abstract public function open(string $path, string $name): bool;

    /**
     * Read the session data.
     *
     * @param string $sessionId The session ID.
     * @return false|string The session data.
     */
    abstract public function read(string $sessionId): false|string;

    /**
     * Write the session data.
     *
     * @param string $sessionId The session ID.
     * @param false|string $data The session data.
     * @return bool TRUE if the data was written, otherwise FALSE.
     */
    abstract public function write(string $sessionId, string $data): bool;

    /**
     * Check the session ID.
     *
     * @param string $sessionId The session ID.
     * @return bool TRUE if the session is valid, otherwise FALSE.
     */
    protected function checkSession(string $sessionId): bool
    {
        if ($sessionId === $this->sessionId) {
            return true;
        }

        return $this->releaseLock() && $this->getLock($sessionId);
    }

    /**
     * Lock the session.
     *
     * @param string $sessionId The session ID.
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function getLock(string $sessionId): bool
    {
        $this->sessionId = $sessionId;

        return true;
    }

    /**
     * Get the session key.
     *
     * @param string $sessionId The session ID.
     * @return string The session key.
     */
    protected function prepareKey(string $sessionId): string
    {
        return $this->config['prefix'].$sessionId;
    }

    /**
     * Unlock the session.
     *
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function releaseLock(): bool
    {
        $this->sessionId = null;

        return true;
    }
}
