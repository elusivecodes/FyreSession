<?php
declare(strict_types=1);

namespace Fyre\Session\Handlers;

use Fyre\DateTime\DateTime;
use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\Schema\SchemaRegistry;
use Fyre\Schema\TableSchema;
use Fyre\Session\Session;
use Fyre\Session\SessionHandler;

/**
 * DatabaseSessionHandler
 */
class DatabaseSessionHandler extends SessionHandler
{
    protected static array $defaults = [
        'connectionKey' => 'default',
    ];

    protected ConnectionManager $connectionManager;

    protected Connection $db;

    protected TableSchema $schema;

    protected SchemaRegistry $schemaRegistry;

    protected bool $sessionExists = false;

    protected string $table;

    /**
     * New DatabaseSessionHandler constructor.
     *
     * @param Session $session The Session.
     * @param ConnectionManager $connectionManager The ConnectionManager.
     * @param SchemaRegistry $schemaRegistry The SchemaRegistry.
     * @param array $options Options for the handler.
     */
    public function __construct(Session $session, ConnectionManager $connectionManager, SchemaRegistry $schemaRegistry, array $options = [])
    {
        parent::__construct($session, $options);

        $this->connectionManager = $connectionManager;
        $this->schemaRegistry = $schemaRegistry;
    }

    /**
     * Close the session.
     *
     * @return bool TRUE if the session was closed, otherwise FALSE.
     */
    public function close(): bool
    {
        return $this->releaseLock();
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

        $this->db->delete()
            ->from($this->table)
            ->where([
                'id' => $sessionId,
            ])
            ->execute();

        return $this->releaseLock();
    }

    /**
     * Session garbage collector.
     *
     * @param int $expires The maximum session lifetime.
     * @return false|int The number of sessions removed.
     */
    public function gc(int $expires): false|int
    {
        $maxLife = DateTime::now()->subSeconds($expires);

        $this->db->delete()
            ->from($this->table)
            ->where([
                'or' => [
                    [
                        'created <' => $this->schema->getType('created')->toDatabase($maxLife),
                        'modified IS NULL',
                    ],
                    'modified <' => $this->schema->getType('modified')->toDatabase($maxLife),
                ],
            ])
            ->execute();

        return $this->db->affectedRows();
    }

    /**
     * Open the session.
     *
     * @param string $path The session path.
     * @param string $name The session name.
     * @return bool TRUE if the session was opened, otherwise FALSE.
     */
    public function open(string $path, string $name): bool
    {
        $this->db = $this->connectionManager->use($this->config['connectionKey']);

        $this->table = $path;

        $this->schema = $this->schemaRegistry->use($this->db)->describe($this->table);

        return true;
    }

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

        return $result['data'];
    }

    /**
     * Write the session data.
     *
     * @param string $sessionId The session ID.
     * @param false|string $data The session data.
     * @return bool TRUE if the data was written, otherwise FALSE.
     */
    public function write(string $sessionId, string $data): bool
    {
        if (!$this->checkSession($sessionId)) {
            return false;
        }

        $now = DateTime::now();

        if (!$this->sessionExists) {
            $result = $this->db->insert()
                ->into($this->table)
                ->values([
                    [
                        'id' => $sessionId,
                        'data' => $data,
                        'created' => $this->schema->getType('created')->toDatabase($now),
                        'modified' => $this->schema->getType('modified')->toDatabase($now),
                    ],
                ])
                ->execute();
        } else {
            $result = $this->db->update($this->table)
                ->set([
                    'data' => $data,
                    'modified' => $this->schema->getType('modified')->toDatabase($now),
                ])
                ->where([
                    'id' => $sessionId,
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
     * Get the database result for a session ID.
     *
     * @param string $sessionId The session ID.
     * @return array|null The database result.
     */
    protected function getResult(string $sessionId): array|null
    {
        if (!$this->checkSession($sessionId)) {
            return null;
        }

        $result = $this->db
            ->select([
                'data',
            ])
            ->from($this->table)
            ->where([
                'id' => $sessionId,
            ])
            ->execute()
            ->first();

        if (!$result) {
            return null;
        }

        $this->sessionExists = true;

        return $result;
    }
}
