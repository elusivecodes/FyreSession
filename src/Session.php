<?php
declare(strict_types=1);

namespace Fyre\Session;

use
    Closure,
    Fyre\Session\Exceptions\SessionException;

use const
    PHP_SAPI,
    PHP_SESSION_ACTIVE;

use function
    array_key_exists,
    ini_set,
    register_shutdown_function,
    session_destroy,
    session_id,
    session_regenerate_id,
    session_set_save_handler,
    session_start,
    session_status,
    session_write_close,
    time;

/**
 * Session
 */
abstract class Session
{

    protected static SessionHandler $handler;

    protected static array $flashData = [];

    /**
     * Clear session data.
     */
    public static function clear(): void
    {
        $_SESSION = [];
        static::$flashData = [];
    }

    /**
     * Clear session flash data.
     */
    public static function clearFlashData(): void
    {
        foreach (static::$flashData AS $key => $value) {
            if (!array_key_exists($key, static::$flashData)) {
                continue;
            }

            static::delete($key);
        }     
    }

    /**
     * Clear session temporary data.
     */
    public static function clearTempData(): void
    {
        $_SESSION['_temp'] ??= [];

        $now = time();

        foreach ($_SESSION['_temp'] AS $key => $expires) {
            if ($expires > $now || !array_key_exists($key, $_SESSION)) {
                continue;
            }

            static::delete($key);
        }
    }

    /**
     * Close the session.
     * @return bool TRUE if the session was closed, otherwise FALSE.
     */
    public static function close(): bool
    {
        static::clearFlashData();

        return session_write_close();
    }

    /**
     * Retrieve and delete a value from the session.
     * @param string $key The session key.
     * @return mixed The value.
     */
    public static function consume(string $key): mixed
    {
        $value = static::get($key);

        static::delete($key);

        return $value;
    }

    /**
     * Delete a value from the session.
     * @param string $key The session key.
     */
    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
        unset($_SESSION['_flash'][$key]);
        unset($_SESSION['_temp'][$key]);
    }

    /**
     * Destroy the session.
     * @return bool TRUE if the session was destroyed, otherwise FALSE.
     */
    public static function destroy(): bool
    {
        return session_destroy();
    }

    /**
     * Retrieve a value from the session.
     * @param string $key The session key.
     * @return mixed The session value.
     */
    public static function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Get the SessionHandler.
     * @return SessionHandler|null The SessionHandler.
     */
    public static function getHandler(): SessionHandler|null
    {
        return static::$handler ?? null;
    }

    /**
     * Determine if a value exists in the session.
     * @param string $key The session key.
     * @return bool TRUE if the item exists, otherwise FALSE.
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Get the session ID.
     * @return string|null The session ID.
     */
    public static function id(): string|null
    {
        return session_id() ?: null;
    }

    /**
     * Determine if the session is active.
     * @return bool TRUE if the session is active, otherwise FALSE.
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Refresh the session ID.
     * @param bool $deleteOldSession Whether to delete the old session data.
     */
    public static function refresh(bool $deleteOldSession = false): void
    {
        session_regenerate_id($deleteOldSession);

        $_SESSION['_refreshed'] = time();
    }

    /**
     * Register the session handler.
     * @param array $options The options for the session handler.
     */
    public static function register(array $options): void
    {
        if (static::isActive()) {
            throw SessionException::forSessionStarted();
        }

        if (!array_key_exists('className', $options)) {
            throw SessionException::forInvalidClass();
        }

        if (!class_exists($options['className'], true)) {
            throw SessionException::forInvalidClass($options['className']);
        }

        $className = $options['className'];

        static::$handler = new $className($options);

        if (PHP_SAPI === 'cli') {
            $_SESSION ??= [];
        } else {
            $config = static::$handler->getConfig();

            ini_set('session.name', $config['cookieName']);
            ini_set('session.gc_maxlifetime', $config['expires']);
            ini_set('session.save_path', $config['path']);
            ini_set('session.cookie_lifetime', $config['cookieLifetime']);
            ini_set('session.cookie_domain', $config['cookieDomain']);
            ini_set('session.cookie_path', $config['cookiePath']);
            ini_set('session.cookie_secure', $config['cookieSecure']);
            ini_set('session.cookie_samesite', $config['cookieSameSite']);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_trans_sid', 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_cookies', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.lazy_write', 1);
            ini_set('session.sid_length ', 48);
            ini_set('session.sid_bits_per_character', 6);
        }

        register_shutdown_function(
            Closure::fromCallable([static::class, 'close'])
        );

        session_set_save_handler(static::$handler);

        session_start();
    
        static::$handler->checkRefresh();

        static::rotateFlashData();
        static::clearTempData();
    }

    /**
     * Rotate the session flash data.
     */
    public static function rotateFlashData(): void
    {
        static::$flashData = $_SESSION['_flash'] ?? [];

        $_SESSION['_flash'] = [];   
    }

    /**
     * Set a session value.
     * @param string $key The session key.
     * @param mixed $value The session value.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Set a session flash value.
     * @param string $key The session key.
     * @param mixed $value The session value.
     */
    public static function setFlash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = true;

        static::set($key, $value);
    }

    /**
     * Set a session temporary value.
     * @param string $key The session key.
     * @param mixed $value The session value.
     * @param int $expire The expiry time for the value.
     */
    public static function setTemp(string $key, mixed $value, int $expire = 300): void
    {
        $_SESSION['_temp'][$key] = time() + $expire;

        static::set($key, $value);
    }

}
