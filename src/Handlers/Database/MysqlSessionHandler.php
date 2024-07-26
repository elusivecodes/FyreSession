<?php
declare(strict_types=1);

namespace Fyre\Session\Handlers\Database;

use Fyre\DB\Connection;
use Fyre\Session\Handlers\DatabaseSessionHandler;

/**
 * MysqlSessionHandler
 */
class MysqlSessionHandler extends DatabaseSessionHandler
{
    protected Connection $db;

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
                'GET_LOCK('.$this->db->quote($sessionId).', 300)',
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
                'RELEASE_LOCK('.$this->db->quote($this->sessionId).')',
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
