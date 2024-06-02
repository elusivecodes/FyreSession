<?php
declare(strict_types=1);

namespace Fyre\Session;

use SessionHandlerInterface;

use function array_key_exists;
use function array_replace_recursive;
use function headers_sent;
use function ini_get;
use function session_name;
use function session_get_cookie_params;
use function setcookie;
use function strtolower;
use function time;

/**
 * SessionHandler
 */
abstract class SessionHandler implements SessionHandlerInterface
{

    protected static array $defaults = [
        'cookieName' => 'FyreSession',
        'cookieLifetime' => 0,
        'cookieDomain' => '',
        'cookiePath' => '/',
        'cookieSecure' => true,
        'cookieSameSite' => 'Lax',
        'expires' => null,
        'path' => 'sessions',
        'refresh' => 300,
        'cleanup' => false,
        'prefix' => ''
    ];

    protected array $config = [];

    protected string|null $sessionId = null;

    /**
     * New SessionHandler constructor.
     * @param array $options Options for the handler.
     */
    public function __construct(array $options = [])
    {
        $options['expires'] ??= (int) ini_get('session.gc_maxlifetime');

        $this->config = array_replace_recursive(self::$defaults, static::$defaults, $options);
    }

    /**
     * Check session refresh.
     */
    public function checkRefresh(): void
    {
        if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }

        $now = time();

        if (!array_key_exists('_refreshed', $_SESSION)) {
            $_SESSION['_refreshed'] = $now;
        } else if ($_SESSION['_refreshed'] < $now - $this->config['refresh']) {
            Session::refresh($this->config['cleanup']);
        }
    }

    /**
     * Close the session.
     * @return bool TRUE if the session was closed, otherwise FALSE.
     */
    abstract public function close(): bool;

    /**
     * Destroy the session.
     * @return bool TRUE if the session was destroyed, otherwise FALSE.
     */
    abstract public function destroy(string $sessionId): bool;

    /**
     * Session garbage collector.
     * @param int $expires The maximum session lifetime.
     * @return int|false The number of sessions removed.
     */
    abstract public function gc(int $expires): int|false;

    /**
     * Get the session handler options.
     * @return array The session handler options.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Open the session.
     * @param string $path The session path.
     * @param string $name The session name.
     * @return bool TRUE if the session was opened, otherwise FALSE.
     */
    abstract public function open(string $path, string $name): bool;

    /**
     * Read the session data.
     * @param string $sessionId The session ID.
     * @return string|false The session data.
     */
    abstract public function read(string $sessionId): string|false;

    /**
     * Write the session data.
     * @param string $sessionId The session ID.
     * @param string|false $data The session data.
     * @return bool TRUE if the data was written, otherwise FALSE.
     */
    abstract public function write(string $sessionId, string $data): bool;

    /**
     * Check the session ID.
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
     * Destroy the session cookie.
     * @param bool TRUE if the cookie was set, otherwise FALSE.
     */
    protected function destroyCookie(): bool
    {
        if (headers_sent()) {
            return true;
        }

        $params = session_get_cookie_params();

        return setcookie(
            session_name(),
            '',
            [
                'expires' => 0,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite']
            ]
        );
    }

    /**
     * Lock the session.
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
     * @param string $sessionId The session ID.
     * @return string The session key.
     */
    protected function prepareKey(string $sessionId): string
    {
        return $this->config['prefix'].$sessionId;
    }

    /**
     * Unlock the session.
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function releaseLock(): bool
    {
        $this->sessionId = null;

        return false;
    }

}
