<?php
declare(strict_types=1);

namespace Fyre\Session\Handlers;

use Fyre\DateTime\DateTime;
use Fyre\DB\ConnectionManager;
use Fyre\DB\Connection;
use Fyre\Schema\SchemaRegistry;
use Fyre\Schema\TableSchema;
use Fyre\Session\SessionHandler;
use SessionHandlerInterface;

/**
 * DatabaseSessionHandler
 */
class DatabaseSessionHandler extends SessionHandler implements SessionHandlerInterface
{

    protected static array $defaults = [
        'connectionKey' => 'default'
    ];

    protected Connection $db;

    protected TableSchema $schema;

    protected string $table;

    protected bool $sessionExists = false;

    /**
     * Close the session.
     * @return bool TRUE if the session was closed, otherwise FALSE.
     */
    public function close(): bool
    {
        return $this->releaseLock();
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

        $result = $this->db->builder()
            ->table($this->table)
            ->where([
                'id' => $sessionId
            ])
            ->delete()
            ->execute();

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
        $maxLife = DateTime::now()->sub($expires, 'seconds');

        $this->db->builder()
            ->table($this->table)
            ->where([
                'or' => [
                    [
                        'created <' => $this->schema->getType('created')->toDatabase($maxLife),
                        'modified IS NULL'
                    ],
                    'modified <' => $this->schema->getType('modified')->toDatabase($maxLife)
                ]
            ])
            ->delete()
            ->execute();

        return $this->db->affectedRows();
    }

    /**
     * Open the session.
     * @param string $path The session path.
     * @param string $name The session name.
     * @return bool TRUE if the session was opened, otherwise FALSE.
     */
    public function open(string $path, string $name): bool
    {
        $this->db = ConnectionManager::use($this->config['connectionKey']);

        $this->table = $path;

        $this->schema = SchemaRegistry::getSchema($this->db)
            ->describe($this->table);

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

        $result = $this->db->builder()
            ->table($this->table)
            ->select('data')
            ->where([
                'id' => $sessionId
            ])
            ->execute()
            ->first();

        if (!$result) {
            return '';
        }

        $this->sessionExists = true;

        return $result['data'];
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

        $now = DateTime::now();

        if (!$this->sessionExists) {
            $result = $this->db->builder()
                ->table($this->table)
                ->insert([
                    'id' => $sessionId,
                    'data' => $data,
                    'created' => $this->schema->getType('created')->toDatabase($now)
                ])
                ->execute();
        } else {
            $result = $this->db->builder()
                ->table($this->table)
                ->where([
                    'id' => $sessionId
                ])
                ->update([
                    'data' => $data,
                    'modified' => $this->schema->getType('modified')->toDatabase($now)
                ])
                ->execute();
        }

        if (!$result) {
            return false;
        }

        $this->sessionExists = true;

        return true;
    }

    /**
     * Lock the session.
     * @param string $sessionId The session ID.
     * @return bool TRUE if the session was locked, otherwise FALSE.
     */
    protected function getLock(string $sessionId): bool
    {
        $result = $this->db->builder()
            ->select('GET_LOCK('.$this->db->quote($sessionId).', 300)')
            ->execute()
            ->first();

        if (!$result) {
            return false;
        }

        $this->sessionId = $sessionId;

        return true;
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

        $result = $this->db->builder()
            ->select('RELEASE_LOCK('.$this->db->quote($this->sessionId).')')
            ->execute()
            ->first();

        if (!$result) {
            return false;
        }

        $this->sessionId = null;
        $this->sessionExists = false;

        return true;
    }

}
