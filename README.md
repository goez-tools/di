# Goez Dependency Injection Container

[![Build Status](https://travis-ci.org/goez-tools/di.svg?branch=master)](https://travis-ci.org/goez-tools/di)

A simple dependency injection container which was inspired by Laravel Service Container.

## Features

* Nested dependency injection.
* Interface binding.

## TODO

* Method injection

## Installation

```bash
$ composer require goez/di
```

## Usage

### Initialize

```php
use Goez\Di\Container;
$container = Container::createInstance();
```

### `make($name[, $arguments])`

Make an instance:

```php
class App
{
    private $appName;

    public function __construct($appName = 'ThisApp')
    {
        $this->appName = $appName;
    }
    
    public function getAppName()
    {
        return $this->appName;
    }
}

$app = $container->make(App::class);
echo $app->getAppName(); // ThisApp

$app = $container->make(App::class, ['MyApp']);
echo $app->getAppName(); // MyApp
```

Inject object by type-hint:

```php
class App
{
    private $auth;
    private $appName;

    public function __construct(Auth $auth, $appName = 'ThisApp')
    {
        $this->auth = $auth;
        $this->appName = $appName;
    }
}

class Auth {}

$app = $container->make(App::class);
```

Nested dependency injection:

```php
class App
{
    private $auth;
    private $appName;

    public function __construct(Auth $auth, $appName = 'ThisApp')
    {
        $this->auth = $auth;
        $this->appName = $appName;
    }
}

class Auth 
{
    private $db;
    
    public function __construct(Db $db)
    {
        $this->db = $db;
    }
}

$app = $container->make(App::class);
```

### `bind($name, $className|$closure)`

Binding by key:

```php
$container->bind('db', function ($container) {
    return new Db();
});
$db = $container->make('db');
```

Binding by interface:

```php
interface DbInterface {}

class Db implements DbInterface {}

$container->bind(DbInterface::class, Db::class);
$db = $container->make(DbInterface::class);
```

### `instance($name, $instance)`

Bind an existed instance.

```php
interface DbInterface {}

class Db implements DbInterface {}

$container->instance(DbInterface::class, new Db());
$db1 = $container->make(DbInterface::class);

$container->instance(DbInterface::class, new Db());
$db2 = $container->make(DbInterface::class);

assert($db1 !== $db2); // true
```

### `singleton($name, $instance|$closure)`

Create singleton instance by closure.

```php
interface DbInterface {}

class Db implements DbInterface {}

$container->singleton(DbInterface::class, function (Container $c) {
    return $c->make(Db::class);
    // Or
    return new Db();
});
$db1 = $container->make(DbInterface::class);
$db2 = $container->make(DbInterface::class);

assert($db1 === $db2);
```

Create singleton instance by an existed instance.

```php
interface DbInterface {}

class Db implements DbInterface {}

$container->singleton(DbInterface::class, new Db());
$db1 = $container->make(DbInterface::class);
$db2 = $container->make(DbInterface::class);

assert($db1 === $db2);
```

## License

MIT