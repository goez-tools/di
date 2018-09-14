<?php

use Goez\Di\Container;
use PHPUnit\Framework\TestCase;
use Stub\App;
use Stub\Auth;
use Stub\Command;
use Stub\Db;
use Stub\DbAuth;
use Stub\HttpAuth;
use Stub\Issue;
use Stub\Session;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    protected function setUp()
    {
        $this->container = Container::getInstance();
    }

    protected function tearDown()
    {
        Container::resetInstance();
    }

    /**
     * @test
     */
    public function it_should_create_instance_of_container()
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    /**
     * @test
     */
    public function it_should_be_a_singlton()
    {
        $container1 = Container::getInstance();
        $container2 = Container::getInstance();
        $this->assertSame($container1, $container2);
    }

    /**
     * @test
     */
    public function it_should_reset_instance()
    {
        $container1 = Container::getInstance();
        Container::resetInstance();
        $container2 = Container::getInstance();
        $this->assertNotSame($container1, $container2);
    }

    /**
     * @test
     */
    public function it_should_bind_and_make_object_of_class()
    {
        $this->container->bind(Db::class, function () {
            return new Db();
        });
        $object = $this->container->make(Db::class);
        $this->assertInstanceOf(Db::class, $object);
    }

    /**
     * @test
     */
    public function it_should_bind_with_closure_and_make_an_object_of_interface()
    {
        $this->container->bind(Auth::class, function () {
            return new DbAuth(new Db());
        });
        $object = $this->container->make(Auth::class);
        $this->assertInstanceOf(DbAuth::class, $object);
    }

    /**
     * @test
     */
    public function it_should_make_object_of_class()
    {
        $db = $this->container->make(Db::class);
        $this->assertInstanceOf(Db::class, $db);
    }

    /**
     * @test
     */
    public function it_should_get_null_with_non_exist_class()
    {
        $object = $this->container->make('NonExist');
        $this->assertNull($object);
    }

    /**
     * @test
     */
    public function it_should_bind_with_class_name_and_make_an_object_of_interface()
    {
        $this->container->bind(Auth::class, HttpAuth::class);
        $object = $this->container->make(Auth::class);
        $this->assertInstanceOf(HttpAuth::class, $object);
    }

    /**
     * @test
     */
    public function it_should_auto_bind_and_make_object_from_arguments()
    {
        /** @var DbAuth $object */
        $object = $this->container->make(DbAuth::class);
        $db = $object->getDb();
        $this->assertInstanceOf(Db::class, $db);
    }

    /**
     * @test
     */
    public function it_should_bind_and_make_object_from_arguments_nested()
    {
        $this->container->bind(Auth::class, DbAuth::class);
        $object = $this->container->make(App::class);
        $this->assertInstanceOf(App::class, $object);
        $this->assertEquals('ThisApp', $object->getAppName());
    }

    /**
     * @test
     */
    public function it_should_skip_scalar_arguments()
    {
        $expectedNamespace = 'namespace';
        /** @var Session $object */
        $object = $this->container->make(Session::class, [$expectedNamespace]);
        $this->assertInstanceOf(Session::class, $object);
        $this->assertEquals($expectedNamespace, $object->getNamespace());
    }

    /**
     * @test
     */
    public function it_should_skip_given_object()
    {
        $db = new Db();
        /** @var DbAuth $object */
        $object = $this->container->make(DbAuth::class, [$db]);
        $this->assertInstanceOf(DbAuth::class, $object);
        $this->assertSame($db, $object->getDb());
    }

    /**
     * @test
     */
    public function it_should_skip_given_objects_and_scalar_argument()
    {
        $db = new Db();
        $dbAuth = new DbAuth($db);
        $session = new Session('test');
        /** @var App $object */
        $object = $this->container->make(App::class, [$dbAuth, $session, 'MyApp']);
        $this->assertInstanceOf(App::class, $object);
        $this->assertSame($dbAuth, $object->getAuth());
        $this->assertSame($session, $object->getSession());
    }

    /**
     * @test
     */
    public function it_should_auto_bind_and_make_object_from_arguments_nested_with_scalar_value()
    {
        $expectedAppName = 'MyApp';
        $this->container->bind(Auth::class, DbAuth::class);
        /** @var App $object */
        $object = $this->container->make(App::class, [$expectedAppName]);
        $this->assertInstanceOf(App::class, $object);
        $this->assertEquals($expectedAppName, $object->getAppName());
    }

    /**
     * @test
     */
    public function it_should_make_object_with_multi_scalar_parameters()
    {
        $expectedName = 'MyCommand';
        /** @var Command $object */
        $object = $this->container->make(Command::class, [$expectedName]);
        $this->assertInstanceOf(Command::class, $object);
        $this->assertEquals($expectedName, $object->getName());
        $this->assertEquals('Hello', $object->getMessage());
    }

    /**
     * @test
     */
    public function it_should_make_object_with_multi_scalar_parameters_and_replaced_by_given_arguments()
    {
        $expectedName = 'MyCommand';
        /** @var Command $object */
        $object = $this->container->make(Command::class, [$expectedName, 'World']);
        $this->assertInstanceOf(Command::class, $object);
        $this->assertEquals($expectedName, $object->getName());
        $this->assertEquals('World', $object->getMessage());
    }

    /**
     * @test
     */
    public function it_should_bind_an_instance_of_interface()
    {
        $auth = new HttpAuth();
        $this->container->instance(Auth::class, $auth);
        $object = $this->container->make(Auth::class);
        $this->assertInstanceOf(HttpAuth::class, $object);
    }

    /**
     * @test
     */
    public function it_should_bind_an_instance_of_custom_name()
    {
        $auth = new HttpAuth();
        $injectionName = 'auth_instance';
        $this->container->instance($injectionName, $auth);
        $object = $this->container->make($injectionName);
        $this->assertInstanceOf(HttpAuth::class, $object);
    }

    /**
     * @test
     */
    public function it_should_replace_an_instance()
    {
        $this->container->instance(Auth::class, new HttpAuth());
        $object1 = $this->container->make(Auth::class);

        $this->container->instance(Auth::class, new HttpAuth());
        $object2 = $this->container->make(Auth::class);

        $this->assertInstanceOf(HttpAuth::class, $object1);
        $this->assertInstanceOf(HttpAuth::class, $object2);
        $this->assertNotSame($object1, $object2);
    }

    /**
     * @test
     */
    public function it_should_get_a_singleton_instance_from_closure()
    {
        $this->container->singleton(Auth::class, function () {
            return new HttpAuth();
        });
        $object1 = $this->container->make(Auth::class);
        $object2 = $this->container->make(Auth::class);
        $this->assertInstanceOf(HttpAuth::class, $object1);
        $this->assertInstanceOf(HttpAuth::class, $object2);
        $this->assertSame($object1, $object2);
    }

    /**
     * @test
     */
    public function it_should_get_a_singleton_instance_from_instance()
    {
        $this->container->singleton(Auth::class, new HttpAuth());
        $object1 = $this->container->make(Auth::class);
        $object2 = $this->container->make(Auth::class);
        $this->assertInstanceOf(HttpAuth::class, $object1);
        $this->assertInstanceOf(HttpAuth::class, $object2);
        $this->assertSame($object1, $object2);
    }

    /**
     * @test
     */
    public function it_could_pass_zero_as_parameter()
    {
        $object1 = $this->container->make(Issue::class, [0]);
        $this->assertInstanceOf(Issue::class, $object1);
        $this->assertSame(0, $object1->getId());
    }

    /**
     * @test
     */
    public function it_should_return_false_when_object_is_not_registered()
    {
        $this->assertFalse($this->container->has('example'));
    }

    /**
     * @test
     */
    public function it_should_return_true_when_instance_is_registered()
    {
        $this->container->instance('example', new stdClass());
        $this->assertTrue($this->container->has('example'));
    }

    /**
     * @test
     */
    public function it_should_return_true_when_binding_is_set()
    {
        $this->container->bind('example', function () {
            return new stdClass();
        });
        $this->assertTrue($this->container->has('example'));
    }

    /**
     * @test
     */
    public function it_should_return_true_when_singleton_is_set()
    {
        $this->container->singleton('example', function () {
            return new stdClass();
        });
        $this->assertTrue($this->container->has('example'));
    }
}
