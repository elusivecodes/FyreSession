# FyreSession

**FyreSession** is a free, session library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Methods](#methods)
- [Session Handlers](#session-handlers)
    - [Database](#database)
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


## Methods

**Close**

Close the session.

```php
Session::close();
```

**Consume**

Retrieve and delete a value from the session.

- `$key` is a string representing the session key.

```php
$value = Session::consume($key);
```

**Delete**

Delete a value from the session.

- `$key` is a string representing the session key.

```php
Session::delete($key);
```

**Destroy**

Destroy the session.

```php
Session::destroy();
```

**Get**

Retrieve a value from the session.

- `$key` is a string representing the session key.

```php
$value = Session::get($key);
```

**Has**

Determine if a value exists in the session.

- `$key` is a string representing the session key.

```php
$has = Session::has($key);
```

**ID**

Get the session ID.

```php
$id = Session::id();
```

**Is Active**

Determine if the session is active.

```php
$isActive = Session::isActive();
```

**Refresh**

Refresh the session ID.

- `$deleteOldSession` is a boolean indicating whether to delete the old session, and will default to *false*.

```php
Session::refresh($deleteOldSession);
```

**Register**

Register the session handler.

- `$options` is an array containing configuration options.

```php
Session::register($options);
```

**Set**

Set a session value.

- `$key` is a string representing the session key.
- `$value` is the value to set.

```php
Session::set($key, $value);
```

**Set Flash**

Set a session flash value.

- `$key` is a string representing the session key.
- `$value` is the value to set.

```php
Session::setFlash($key, $value);
```

**Set Temp**

Set a session temporary value.

- `$key` is a string representing the session key.
- `$value` is the value to set.
- `$expire` is a number indicating the number of seconds the value will be valid, and will default to *300*.

```php
Session::setTemp($key, $value, $expire);
```


## Session Handlers

You can load a specific session handler by specifying the `className` option of the `$options` variable of the `register` method.

Custom session handlers can be created by extending `\Fyre\Session\SessionHandler` and implementing the `SessionHandlerInterface`.


### Database

The Database session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\FileSessionHandler`.
    - `cookieName` is a string representing the cookie name, and will default to "*FyreSession*".
    - `cookieLifetime` is a number representing the cookie lifetime, and will default to *0*.
    - `cookieDomain` is a string representing the cookie domain, and will default to "".
    - `cookiePath` is a string representing the cookie path, and will default to "*/*".
    - `cookieSecure` is a boolean indicating whether to set a secure cookie, and will default to *true*.
    - `cookieSameSite` is a string representing the cookie same site, and will default to "*Lax*".
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `refresh` is a number representing the number of seconds before refreshing the session ID, and will default to *300*.
    - `cleanup` is a boolean indicating whether to delete the old session on refresh, and will default to *false*.
    - `prefix` is a string representing the session key prefix, and will default to "".
    - `connectionKey` is a string representing the *Connection* key and will default to "*default*".
    - `path` is a string representing the table name, and will default to "*sessions*".

```php
Session::register($options);
```

### File

The File session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\FileSessionHandler`.
    - `cookieName` is a string representing the cookie name, and will default to "*FyreSession*".
    - `cookieLifetime` is a number representing the cookie lifetime, and will default to *0*.
    - `cookieDomain` is a string representing the cookie domain, and will default to "".
    - `cookiePath` is a string representing the cookie path, and will default to "*/*".
    - `cookieSecure` is a boolean indicating whether to set a secure cookie, and will default to *true*.
    - `cookieSameSite` is a string representing the cookie same site, and will default to "*Lax*".
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `refresh` is a number representing the number of seconds before refreshing the session ID, and will default to *300*.
    - `cleanup` is a boolean indicating whether to delete the old session on refresh, and will default to *false*.
    - `prefix` is a string representing the session key prefix, and will default to "".
    - `path` is a string representing the directory path, and will default to "*sessions*".

```php
Session::register($options);
```

### Memcached

The Memcached session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\MemcachedSessionHandler`.
    - `cookieName` is a string representing the cookie name, and will default to "*FyreSession*".
    - `cookieLifetime` is a number representing the cookie lifetime, and will default to *0*.
    - `cookieDomain` is a string representing the cookie domain, and will default to "".
    - `cookiePath` is a string representing the cookie path, and will default to "*/*".
    - `cookieSecure` is a boolean indicating whether to set a secure cookie, and will default to *true*.
    - `cookieSameSite` is a string representing the cookie same site, and will default to "*Lax*".
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `refresh` is a number representing the number of seconds before refreshing the session ID, and will default to *300*.
    - `cleanup` is a boolean indicating whether to delete the old session on refresh, and will default to *false*.
    - `prefix` is a string representing the session key prefix, and will default to "*session:*".
    - `host` is a string representing the Memcached host, and will default to "*127.0.0.1*".
    - `port` is a number indicating the Memcached port, and will default to *11211*.
    - `weight` is a string representing the server weight, and will default to *1*.

```php
Session::register($options);
```

### Redis

The Redis session handler can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Session\Handlers\RedisSessionHandler`.
    - `cookieName` is a string representing the cookie name, and will default to "*FyreSession*".
    - `cookieLifetime` is a number representing the cookie lifetime, and will default to *0*.
    - `cookieDomain` is a string representing the cookie domain, and will default to "".
    - `cookiePath` is a string representing the cookie path, and will default to "*/*".
    - `cookieSecure` is a boolean indicating whether to set a secure cookie, and will default to *true*.
    - `cookieSameSite` is a string representing the cookie same site, and will default to "*Lax*".
    - `expires` is a number representing the maximum lifetime of a session, and will default to the `session.gc_maxlifetime` PHP setting.
    - `refresh` is a number representing the number of seconds before refreshing the session ID, and will default to *300*.
    - `cleanup` is a boolean indicating whether to delete the old session on refresh, and will default to *false*.
    - `prefix` is a string representing the session key prefix, and will default to "*session:*".
    - `host` is a string representing the Redis host, and will default to "*127.0.0.1*".
    - `password` is a string representing the Redis password
    - `port` is a number indicating the Redis port, and will default to *6379*.
    - `database` is a string representing the Redis database.
    - `timeout` is a number indicating the connection timeout.

```php
Session::register($options);
```