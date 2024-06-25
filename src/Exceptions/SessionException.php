<?php
declare(strict_types=1);

namespace Fyre\Session\Exceptions;

use RunTimeException;

/**
 * SessionException
 */
class SessionException extends RunTimeException
{
    public static function forAuthFailed(): static
    {
        return new static('Session handler authentication failed');
    }

    public static function forConnectionError(string $message = ''): static
    {
        return new static('Session handler connection error: '.$message);
    }

    public static function forConnectionFailed(): static
    {
        return new static('Session handler connection failed');
    }

    public static function forInvalidClass(string $className = ''): static
    {
        return new static('Cache handler class not found: '.$className);
    }

    public static function forInvalidDatabase(string $database): static
    {
        return new static('Session handler invalid database: '.$database);
    }

    public static function forSessionStarted(): static
    {
        return new static('Session already started');
    }
}
