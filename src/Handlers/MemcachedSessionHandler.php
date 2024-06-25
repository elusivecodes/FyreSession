<?php
declare(strict_types=1);

namespace Fyre\Session\Handlers;

use Exception;
use Fyre\Session\Exceptions\SessionException;
use Fyre\Session\SessionHandler;
use Memcached;

use function sleep;

/**
 * MemcachedSessionHelper
 */
class MemcachedSessionHandler extends SessionHandler
{
    protected static array $defaults = [
        'host' => '127.0.0.1',
        'port' => 11211,
        'weight' => 1,
        'prefix' => 'session:',
    ];

    protected Memcached $connection;

    /**
     * Close the session.
     *
     * @return bool TRUE if the session was closed, otherwise FALSE.
     */
    public function close(): bool
    {
        if (!$this->releaseLock()) {
            return false;
        }

        $this->connection->quit();

        return true;
    }

    /**
     * Destroy the session.
     *
     * @return bool TRUE if the session was destroyed, otherwise FALSE.
     */
    public function destroy(string $sessionId): bool
    {
        if (!$this->checkSession($sessionId)) {
            return false;
        }

        $key = $this->prepareKey($sessionId);

        if (!$this->connection->delete($key)) {
            return false;
        }

        if (!$this->destroyCookie()) {
            return false;
        }

        return $this->releaseLock();
    }

    /**
     * Session garbage collector.
     *
     * @param int $expires The maximum session lifetime.
     * @return int|false The number of sessions removed.
     */
    public function gc(int $expires): false|int
    {
        return 1;
    }

    /**
     * Open the session.
     *
     * @param string $path The session path.
     * @param string $name The session name.
     * @return bool TRUE if the session was opened, otherwise FALSE.
     *
     * @throws SessionException if the connection is not valid.
     */
    public function open(string $path, string $name): bool
    {
        try {
            $this->connection = new Memcached();

            $this->connection->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

            $this->connection->addServer(
                $this->config['host'],
                (int) $this->config['port'],
                $this->config['weight']
            );

            if (!$this->getStats()) {
                throw SessionException::forConnectionFailed();
            }
        } catch (SessionException $e) {
            throw $e;
        } catch (Exception $e) {
            throw SessionException::forConnectionError($e->getMessage());
        }

        return true;
    }

    /**
     * Read the session data.
     *
     * @param string $sessionId The session ID.
     * @return string|false The session data.
     */
    public function read(string $sessionId): false|string
    {
        if (!$this->checkSession($sessionId)) {
            return '';
        }

        $key = $this->prepareKey($sessionId);

        $value = $this->connection->get($key);

        if ($this->connection->getResultCode() === Memcached::RES_NOTFOUND) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Write the session data.
     *
     * @param string $sessionId The session ID.
     * @param string|false $data The session data.
     * @return bool TRUE if the data was written, otherwise FALSE.
     */
    public function write(string $sessionId, string $data): bool
    {
        if (!$this->checkSession($sessionId)) {
            return false;
        }

        $key = $this->prepareKey($sessionId);

        if (!$this->connection->set($key, $data, $this->config['expires'])) {
            return false;
        }

        return true;
    }

    /**
     * Lock the session.
     *
     * @param string $sessionId The session ID.
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function getLock(string $sessionId): bool
    {
        $key = $this->prepareKey($sessionId);

        $lockKey = $key.':lock';
        $attempt = 0;

        do {
            if ($this->connection->get($lockKey)) {
                sleep(1);

                continue;
            }

            if ($this->connection->set($lockKey, '1', 300)) {
                $this->sessionId = $sessionId;

                return true;
            }
        } while ($attempt++ < 30);

        return false;
    }

    /**
     * Get memcached stats.
     *
     * @return array|null The memcached stats.
     */
    protected function getStats(): array|null
    {
        $stats = $this->connection->getStats();

        $server = $this->config['host'].':'.$this->config['port'];

        return $stats[$server] ?? null;
    }

    /**
     * Unlock the session.
     *
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function releaseLock(): bool
    {
        if (!$this->sessionId) {
            return true;
        }

        $key = $this->prepareKey($this->sessionId);

        if (!$this->connection->delete($key.':lock')) {
            return false;
        }

        $this->sessionId = null;

        return true;
    }
}
