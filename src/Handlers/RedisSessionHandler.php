<?php
declare(strict_types=1);

namespace Fyre\Session\Handlers;

use Fyre\Session\SessionHandler;
use Fyre\Session\Exceptions\SessionException;
use Redis;
use RedisException;
use SessionHandlerInterface;

use function sleep;

/**
 * RedisSessionHelper
 */
class RedisSessionHandler extends SessionHandler implements SessionHandlerInterface
{

    protected static array $defaults =[ 
        'host' => '127.0.0.1',
        'password' => null,
        'port' => 6379,
        'database' => null,
        'timeout' => 0,
        'prefix' => 'session:'
    ];

    protected Redis $connection;

    /**
     * Close the session.
     * @return bool TRUE if the session was closed, otherwise FALSE.
     */
    public function close(): bool
    {
        if (!$this->releaseLock()) {
            return false;
        }

        $this->connection->close();

        return true;
    }

    /**
     * Destroy the session.
     * @return bool TRUE if the session was destroyed, otherwise FALSE.
     */
    public function destroy(string $sessionId): bool
    {
        if (!$this->checkSession($sessionId)) {
            return false;
        }

        $key = $this->prepareKey($sessionId);

        if (!$this->connection->del($key)) {
            return false;
        }

        if (!$this->destroyCookie()) {
            return false;
        }

        return $this->releaseLock();
    }

    /**
     * Session garbage collector.
     * @param int $expires The maximum session lifetime.
     * @return int|false The number of sessions removed.
     */
    public function gc(int $expires): int|false
    {
        return 1;
    }

    /**
     * Open the session.
     * @param string $path The session path.
     * @param string $name The session name.
     * @return bool TRUE if the session was opened, otherwise FALSE.
     * @throws SessionException if the connection is not valid.
     */
    public function open(string $path, string $name): bool
    {
        try {
            $this->connection = new Redis();
    
            if (!$this->connection->connect($this->config['host'], (int) $this->config['port'], $this->config['timeout'])) {
                throw SessionException::forConnectionFailed();
            }

            if ($this->config['password'] && !$this->connection->auth($this->config['password'])) {
                throw SessionException::forAuthFailed();
            }

            if ($this->config['database'] && !$this->connection->select($this->config['database'])) {
                throw CacheException::forInvalidDatabase($this->config['database']);
            }

        } catch (RedisException $e) {
            throw SessionException::forConnectionError($e->getMessage());
        }

        return true;
    }

    /**
     * Read the session data.
     * @param string $sessionId The session ID.
     * @return string|false The session data.
     */
    public function read(string $sessionId): string|false
    {
        if (!$this->checkSession($sessionId)) {
            return false;
        }

        $key = $this->prepareKey($sessionId);

        return (string) $this->connection->get($key);
    }

    /**
     * Write the session data.
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

        if (!$this->connection->setEx($key, $this->config['expires'], $data)) {
            return false;
        }

        return true;
    }

    /**
     * Lock the session.
     * @param string $sessionId The session ID.
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function getLock(string $sessionId): bool
    {
        $key = $this->prepareKey($sessionId);

        $lockKey = $key.':lock';
        $attempt = 0;

        do {
            if ($this->connection->ttl($lockKey) > 0) {
                sleep(1);
                continue;
            }

            if ($this->connection->setEx($lockKey, 300, '1')) {
                $this->sessionId = $sessionId;

                return true;
            }
        } while ($attempt++ < 30);

        return false;
    }

    /**
     * Unlock the session.
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function releaseLock(): bool
    {
        if (!$this->sessionId) {
            return true;
        }

        $key = $this->prepareKey($this->sessionId);

        if (!$this->connection->del($key.':lock')) {
            return false;
        }

        $this->sessionId = null;

        return true;
    }

}
