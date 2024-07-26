<?php
declare(strict_types=1);

namespace Fyre\Session\Handlers\Database;

use Fyre\DB\Connection;
use Fyre\Session\Handlers\DatabaseSessionHandler;

use function stream_get_contents;

/**
 * PostgresSessionHandler
 */
class PostgresSessionHandler extends DatabaseSessionHandler
{
    protected Connection $db;

    /**
     * Read the session data.
     *
     * @param string $sessionId The session ID.
     * @return false|string The session data.
     */
    public function read(string $sessionId): false|string
    {
        $result = $this->getResult($sessionId);

        if (!$result) {
            return '';
        }

        return stream_get_contents($result['data']);
    }

    /**
     * Lock the session.
     *
     * @param string $sessionId The session ID.
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function getLock(string $sessionId): bool
    {
        $result = $this->db
            ->select([
                'pg_advisory_lock(hashtext('.$this->db->quote($sessionId).'))',
            ])
            ->execute()
            ->first();

        if (!$result) {
            return false;
        }

        return parent::getLock($sessionId);
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

        $result = $this->db
            ->select([
                'pg_advisory_unlock(hashtext('.$this->db->quote($this->sessionId).'))',
            ])
            ->execute()
            ->first();

        if (!$result) {
            return false;
        }

        if (!parent::releaseLock()) {
            return false;
        }

        $this->sessionExists = false;

        return true;
    }
}
