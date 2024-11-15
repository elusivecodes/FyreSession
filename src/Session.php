<?php
declare(strict_types=1);

namespace Fyre\Session;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Session\Exceptions\SessionException;
use Fyre\Session\Handlers\FileSessionHandler;

use function array_key_exists;
use function array_replace_recursive;
use function ini_get;
use function ini_set;
use function session_destroy;
use function session_get_cookie_params;
use function session_id;
use function session_name;
use function session_regenerate_id;
use function session_register_shutdown;
use function session_set_save_handler;
use function session_start;
use function session_status;
use function setcookie;
use function time;

use const PHP_SAPI;
use const PHP_SESSION_ACTIVE;

/**
 * Session
 */
class Session
{
    protected static array $defaults = [
        'cookie' => [
            'name' => 'FyreSession',
            'expires' => 0,
            'domain' => '',
            'path' => '/',
            'secure' => true,
            'sameSite' => 'Lax',
        ],
        'expires' => null,
        'path' => 'sessions',
        'handler' => [
            'className' => FileSessionHandler::class,
        ],
    ];

    protected array $config;

    protected SessionHandler $handler;

    /**
     * New Session constructor.
     *
     * @param Container $container The Container.
     */
    public function __construct(Container $container, Config $config)
    {
        $this->config = array_replace_recursive(static::$defaults, $config->get('Session', []));
        $this->config['expires'] ??= (int) ini_get('session.gc_maxlifetime');

        $options = $this->config['handler'] ?? [];

        if (!array_key_exists('className', $options)) {
            throw SessionException::forInvalidClass();
        }

        if (!class_exists($options['className'], true)) {
            throw SessionException::forInvalidClass($options['className']);
        }

        $options['expires'] ??= $this->config['expires'];

        ini_set('session.name', $this->config['cookie']['name']);
        ini_set('session.gc_maxlifetime', $this->config['expires']);
        ini_set('session.save_path', $this->config['path']);
        ini_set('session.cookie_lifetime', $this->config['cookie']['expires']);
        ini_set('session.cookie_domain', $this->config['cookie']['domain']);
        ini_set('session.cookie_path', $this->config['cookie']['path']);
        ini_set('session.cookie_secure', $this->config['cookie']['secure']);
        ini_set('session.cookie_samesite', $this->config['cookie']['sameSite']);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.lazy_write', 1);
        ini_set('session.sid_length ', 48);
        ini_set('session.sid_bits_per_character', 6);

        $this->handler = $container->build($options['className'], ['session' => $this, 'options' => $options]);

        session_set_save_handler($this->handler);
        session_register_shutdown();
    }

    /**
     * Clear session data.
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Retrieve and delete a value from the session.
     *
     * @param string $key The session key.
     * @return mixed The value.
     */
    public function consume(string $key): mixed
    {
        $value = $this->get($key);

        $this->delete($key);

        return $value;
    }

    /**
     * Delete a value from the session.
     *
     * @param string $key The session key.
     * @return Session The Session.
     */
    public function delete(string $key): static
    {
        unset($_SESSION[$key]);
        unset($_SESSION['_flash'][$key]);
        unset($_SESSION['_temp'][$key]);

        return $this;
    }

    /**
     * Destroy the session.
     */
    public function destroy(): void
    {
        session_destroy();

        $this->clear();
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key The session key.
     * @return mixed The session value.
     */
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Get the SessionHandler.
     *
     * @return SessionHandler|null The SessionHandler.
     */
    public function getHandler(): SessionHandler|null
    {
        return $this->handler ?? null;
    }

    /**
     * Determine if a value exists in the session.
     *
     * @param string $key The session key.
     * @return bool TRUE if the item exists, otherwise FALSE.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Get the session ID.
     *
     * @return string|null The session ID.
     */
    public function id(): string|null
    {
        return session_id() ?: null;
    }

    /**
     * Determine if the session is active.
     *
     * @return bool TRUE if the session is active, otherwise FALSE.
     */
    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Refresh the session ID.
     *
     * @param bool $deleteOldSession Whether to delete the old session data.
     */
    public function refresh(bool $deleteOldSession = false): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            $params + ['expires' => 1]
        );

        session_regenerate_id($deleteOldSession);
    }

    /**
     * Set a session value.
     *
     * @param string $key The session key.
     * @param mixed $value The session value.
     * @return Session The Session.
     */
    public function set(string $key, mixed $value): static
    {
        unset($_SESSION['_flash'][$key]);
        unset($_SESSION['_temp'][$key]);

        $_SESSION[$key] = $value;

        return $this;
    }

    /**
     * Set a session flash value.
     *
     * @param string $key The session key.
     * @param mixed $value The session value.
     * @return Session The Session.
     */
    public function setFlash(string $key, mixed $value): static
    {
        $this->set($key, $value);

        $_SESSION['_flash'][$key] = true;

        return $this;
    }

    /**
     * Set a session temporary value.
     *
     * @param string $key The session key.
     * @param mixed $value The session value.
     * @param int $expire The expiry time for the value.
     * @return Session The Session.
     */
    public function setTemp(string $key, mixed $value, int $expire = 300): static
    {
        $this->set($key, $value);

        $_SESSION['_temp'][$key] = time() + $expire;

        return $this;
    }

    /**
     * Start the session.
     *
     * @throws SessionException if the session is already started, or the handler is not valid.
     */
    public function start(): void
    {
        if (PHP_SAPI === 'cli') {
            $_SESSION = [];

            session_id('cli');

            return;
        }

        if ($this->isActive()) {
            throw SessionException::forSessionStarted();
        }

        session_start();

        $time = $_SESSION['_time'] ?? null;

        if ($time !== null && time() - $time > $this->config['expires']) {
            $this->destroy();
            $this->start();

            return;
        }

        $_SESSION['_time'] = time();

        $this->clearTempData();
        $this->rotateFlashData();
    }

    /**
     * Clear session temporary data.
     */
    protected function clearTempData(): void
    {
        $_SESSION['_temp'] ??= [];

        $now = time();

        foreach ($_SESSION['_temp'] as $key => $expires) {
            if ($expires > $now) {
                continue;
            }

            $this->delete($key);
        }
    }

    /**
     * Rotate the session flash data.
     */
    protected function rotateFlashData(): void
    {
        $_SESSION['_flash'] ??= [];

        foreach ($_SESSION['_flash'] as $key => $value) {
            $_SESSION['_temp'][$key] = 0;
        }

        $_SESSION['_flash'] = [];
    }
}
