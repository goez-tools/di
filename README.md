# Goez Dependency Injection Container

A simple dependency injection container which was inspired by Laravel Service Container.

## Features

* Nested dependency injection.
* Interface binding.

## TODO

* Singleton
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

## License

MIT