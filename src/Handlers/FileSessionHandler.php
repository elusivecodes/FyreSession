<?php
declare(strict_types=1);

namespace Fyre\Session\Handlers;

use Fyre\FileSystem\File;
use Fyre\FileSystem\Folder;
use Fyre\Session\SessionHandler;
use Fyre\Utility\Path;

use function time;

use const LOCK_EX;

/**
 * FileSessionHandler
 */
class FileSessionHandler extends SessionHandler
{
    protected File|null $file = null;

    protected Folder $folder;

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

        $this->file->delete();

        return true;
    }

    /**
     * Session garbage collector.
     *
     * @param int $expires The maximum session lifetime.
     * @return false|int The number of sessions removed.
     */
    public function gc(int $expires): false|int
    {
        $maxLife = time() - $expires;

        $contents = $this->folder->contents();

        $deleted = 0;
        foreach ($contents as $item) {
            if ($item instanceof Folder || $item->modifiedTime() >= $maxLife) {
                continue;
            }

            $deleted++;
            $item->delete();
        }

        return $deleted;
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
        $this->folder = new Folder($path, true);

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
        if (!$this->checkSession($sessionId)) {
            return '';
        }

        return $this->file->contents();
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

        $this->file->truncate();
        $this->file->rewind();
        $this->file->write($data);

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
        $filePath = Path::join($this->folder->path(), $key);

        $this->file = new File($filePath);

        $this->file->open('c+b');
        $this->file->chmod(0600);
        $this->file->lock(LOCK_EX);

        $this->sessionId = $sessionId;

        return true;
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

        $this->file->unlock();
        $this->file->close();
        $this->file = null;

        $this->sessionId = null;

        return true;
    }
}
