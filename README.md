# FyreSession

**FyreSession** is a free, open-source session library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Methods](#methods)
- [Session Handlers](#session-handlers)
    - [Database](#database)
        - [MySQL](#mysql)
        - [Postgres](#postgres)
    - [File](#file)
    - [Memcached](#memcached)
    - [Redis](#redis)



## Installation

**Using Composer**

```
composer require fyre/session
```

In PHP:

```php
use Fyre\Session\Session;
```


## Basic Usage

- `$container` is a [*Container*](https://github.com/elusivecodes/FyreContainer).
- `$config` is a [*Config*](https://github.com/elusivecodes/FyreConfig).

```php
$session = new Session($container, $config);
```

Default configuration options will be resolved from the "*Session*" key in the [*Config*](https://github.com/elusivecodes/FyreConfig).

- `$options` is an array containing configuration options.
    - `cookie` is an array containing session cookie options.
        - `name` is a string representing the cookie name, and will default to "*FyreSession*".
        - `expires` is a number representing the cookie lifetime, and will default to *0*.
        - `domain` is a string representing the cookie domain, and will default to "".
        - `path` is a string representing the cookie path, and will default to "*/*".
        - `secure` is a boolean indicating whether to set a secure cookie, and will default to *true*.
        - `sameSite` is a string representing the cookie same site, and will default to "*Lax*".
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `path` is a string representing the session path, and will default to "*sessions*".
    - `handler`
        - `className` must be set to `\Fyre\Session\Handlers\Database\PostgresSessionHandler`.

```php
$container->use(Config::class)->set('Session', $options);
```

**Autoloading**

It is recommended to bind the *Session* to the [*Container*](https://github.com/elusivecodes/FyreContainer) as a singleton.

```php
$container->singleton(Session::class);
```

Any dependencies will be injected automatically when loading from the [*Container*](https://github.com/elusivecodes/FyreContainer).

```php
$session = $container->use(Session::class);
```


## Methods

**Clear**

Clear the session data.

```php
$session->clear();
```

**Consume**

Retrieve and delete a value from the session.

- `$key` is a string representing the session key.

```php
$value = $session->consume($key);
```

**Delete**

Delete a value from the session.

- `$key` is a string representing the session key.

```php
$session->delete($key);
```

**Destroy**

Destroy the session.

```php
$session->destroy();
```

**Get**

Retrieve a value from the session.

- `$key` is a string representing the session key.

```php
$value = $session->get($key);
```

**Has**

Determine whether a value exists in the session.

- `$key` is a string representing the session key.

```php
$has = $session->has($key);
```

**ID**

Get the session ID.

```php
$id = $session->id();
```

**Is Active**

Determine whether the session is active.

```php
$isActive = $session->isActive();
```

**Refresh**

Refresh the session ID.

- `$deleteOldSession` is a boolean indicating whether to delete the old session, and will default to *false*.

```php
$session->refresh($deleteOldSession);
```

**Set**

Set a session value.

- `$key` is a string representing the session key.
- `$value` is the value to set.

```php
$session->set($key, $value);
```

**Set Flash**

Set a session flash value.

- `$key` is a string representing the session key.
- `$value` is the value to set.

```php
$session->setFlash($key, $value);
```

**Set Temp**

Set a session temporary value.

- `$key` is a string representing the session key.
- `$value` is the value to set.
- `$expire` is a number indicating the number of seconds the value will be valid, and will default to *300*.

```php
$session->setTemp($key, $value, $expire);
```

**Start**

Start the session.

- `$options` is an array containing configuration options.

```php
$session->start($options);
```


## Session Handlers

You can load a specific session handler by specifying the `Session.handler.className` [*Config*](https://github.com/elusivecodes/FyreConfig) option.

Custom session handlers can be created by extending `\Fyre\Session\SessionHandler` and implementing the [`SessionHandlerInterface`](https://www.php.net/manual/en/class.sessionhandlerinterface.php).


### Database

The Database session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\DatabaseSessionHandler`.
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `prefix` is a string representing the session key prefix, and will default to "".
    - `connectionKey` is a string representing the *Connection* key and will default to "*default*".
    - `path` is a string representing the table name, and will default to "*sessions*".

```php
$container->use(Config::class)->set('Session.handler', $options);
```

#### MySQL

The MySQL database session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\Database\MysqlSessionHandler`.
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `prefix` is a string representing the session key prefix, and will default to "".
    - `connectionKey` is a string representing the *Connection* key and will default to "*default*".
    - `path` is a string representing the table name, and will default to "*sessions*".

```php
$container->use(Config::class)->set('Session.handler', $options);
```

#### Postgres

The Postgres database session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\Database\PostgresSessionHandler`.
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `prefix` is a string representing the session key prefix, and will default to "".
    - `connectionKey` is a string representing the *Connection* key and will default to "*default*".
    - `path` is a string representing the table name, and will default to "*sessions*".

```php
$container->use(Config::class)->set('Session.handler', $options);
```

### File

The File session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\FileSessionHandler`.
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `prefix` is a string representing the session key prefix, and will default to "".
    - `path` is a string representing the directory path, and will default to "*sessions*".

```php
$container->use(Config::class)->set('Session.handler', $options);
```

### Memcached

The Memcached session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\MemcachedSessionHandler`.
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `prefix` is a string representing the session key prefix, and will default to "*session:*".
    - `host` is a string representing the Memcached host, and will default to "*127.0.0.1*".
    - `port` is a number indicating the Memcached port, and will default to *11211*.
    - `weight` is a string representing the server weight, and will default to *1*.

```php
$container->use(Config::class)->set('Session.handler', $options);
```

### Redis

The Redis session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\RedisSessionHandler`.
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `prefix` is a string representing the session key prefix, and will default to "*session:*".
    - `host` is a string representing the Redis host, and will default to "*127.0.0.1*".
    - `password` is a string representing the Redis password
    - `port` is a number indicating the Redis port, and will default to *6379*.
    - `database` is a string representing the Redis database.
    - `timeout` is a number indicating the connection timeout.

```php
$container->use(Config::class)->set('Session.handler', $options);
```