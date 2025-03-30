<?php

namespace Hyper;

use Hyper\Helpers\Mail;
use Hyper\Utils\Vite;
use Hyper\Utils\Cache;
use Hyper\Utils\Hash;
use Hyper\Utils\Ping;
use Hyper\Utils\Sanitizer;
use Hyper\Utils\Session;
use Hyper\Utils\Validator;
use Hyper\Utils\Uploader;
use Hyper\Utils\Image;
use Hyper\Utils\Paginator;
use Hyper\Utils\Collect;
use Hyper\Utils\Tracer;

/**
 * The Application class is the main entry point to the framework.
 * 
 * It provides a way to register and resolve services and dependencies, 
 * initialize the application, and setup the environment.
 * 
 * @package hyper
 * @author Shahin Moyshan <shahin.moyshan2@gmail.com>
 */
class Application
{
    /** @var Application Singleton instance of the application */
    public static Application $app;

    /**
     * Dependency injection container.
     * 
     * Manages the application's services and dependencies by providing a way to register and resolve them.
     * 
     * @var Container
     */
    private Container $container;

    /**
     * Application constructor.
     * 
     * Initializes the application by setting up the application instance statically, 
     * initializing the dependency injection container, registering core services, 
     * and binding core services to the container.
     * 
     * @param string $path The path to the application.
     * @param array  $env Environment variables.
     */
    public function __construct(private string $path, private array $env = [])
    {
        // Set the application instance statically.
        self::$app = $this;

        // Initialize the tracer if debug mode is enabled
        if (isset($env['debug']) && $env['debug']) {
            Tracer::trace();
        }

        // Initialize the dependency injection container
        $this->container = new Container;

        // Register core services
        $this->container->singleton(Session::class);
        $this->container->singleton(Request::class);
        $this->container->singleton(Response::class);
        $this->container->singleton(Middleware::class);
        $this->container->singleton(Router::class);
        $this->container->singleton(Translator::class);
        $this->container->singleton(Database::class);
        $this->container->singleton(View::class);
        $this->container->singleton(Vite::class);
        $this->container->singleton(Hash::class);

        // Bind core services
        $this->container->bind(Query::class);
        $this->container->bind(Cache::class);
        $this->container->bind(Ping::class);
        $this->container->bind(Validator::class);
        $this->container->bind(Sanitizer::class);
        $this->container->bind(Uploader::class);
        $this->container->bind(Image::class);
        $this->container->bind(Paginator::class);
        $this->container->bind(Collect::class);
    }

    /**
     * Creates a new instance of the application.
     *
     * @param string $path The path to the root directory of the application.
     * @param array $env An optional array of environment variables.
     *
     * @return self A new instance of the application.
     */
    public static function make(string $path, array $env = []): self
    {
        return new self($path, $env);
    }

    /**
     * Returns the root path of the application.
     *
     * @return string The root path of the application.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retrieves an environment variable's value.
     *
     * @param string $key The name of the environment variable.
     * @param mixed $default The default value to return if the environment variable is not set.
     * 
     * @return mixed The value of the environment variable, or the default value if not set.
     */
    public function getEnv(string $key, $default = null): mixed
    {
        return $this->env[$key] ?? $default;
    }

    /**
     * Merges the provided environment variables with the existing ones.
     *
     * @param array $env An associative array of environment variables to merge.
     *
     * @return void
     */
    public function mergeEnv(array $env): void
    {
        $this->env = array_merge($this->env, $env);
    }

    /**
     * Retrieves the application's dependency injection container.
     *
     * This container manages the application's services and dependencies,
     * providing a way to register and resolve them.
     *
     * @return Container The dependency injection container instance.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Registers a singleton service provider with the application's dependency injection container.
     *
     * Singleton services are registered with the container and returned on each request.
     *
     * @param string $abstract The abstract name or class name of the service to be resolved.
     * @param mixed $concrete The concrete value of the service to be resolved.
     * @return self
     */
    public function singleton(string $abstract, $concrete = null): self
    {
        $this->container->singleton($abstract, $concrete);
        return $this;
    }

    /**
     * Registers a service provider with the application's dependency injection container.
     *
     * Bindings are registered with the container and returned on each request.
     *
     * @param string $abstract The abstract name or class name of the service to be resolved.
     * @param mixed $concrete The concrete value of the service to be resolved.
     * @return self
     */
    public function bind(string $abstract, $concrete = null): self
    {
        $this->container->bind($abstract, $concrete);
        return $this;
    }

    /**
     * Resolves a service or a value from the dependency injection container.
     *
     * @param string $abstract The abstract name or class name of the service or value to be resolved.
     * @return mixed The resolved service or value.
     */
    public function get(string $abstract): mixed
    {
        return $this->container->get($abstract);
    }

    /**
     * Checks if a given abstract has a binding in the container.
     *
     * @param string $abstract The abstract name or class name of the service or value to be checked.
     * @return bool True if the abstract has a binding, false otherwise.
     */
    public function has(string $abstract): bool
    {
        return $this->container->has($abstract);
    }

    /**
     * Applies a callback to the application's container.
     *
     * This method takes a callback function, which receives the container,
     * allowing custom logic to be executed on the application's dependency
     * injection container.
     *
     * @param callable $callback The callback to be applied to the container.
     * @return self
     */
    public function withContainer(callable $callback): self
    {
        $callback($this->container);
        return $this;
    }

    /**
     * Applies a callback to the application's router.
     *
     * This method takes a callback function, which receives the router
     * from the dependency injection container, allowing custom router
     * logic to be executed.
     *
     * @param callable $callback The callback to be applied to the router.
     * @return self
     */
    public function withRouter(callable $callback): self
    {
        $callback($this->container->get(Router::class));
        return $this;
    }

    /**
     * Applies a middleware to the application.
     *
     * This method takes a callback function which receives the middleware
     * manager from the dependency injection container, allowing custom
     * middleware logic to be executed.
     *
     * @param callable $callback The callback to be applied to the middleware manager.
     * @return self
     */
    public function withMiddleware(callable $callback): self
    {
        $callback($this->container->get(Middleware::class));
        return $this;
    }

    /**
     * Runs the application.
     *
     * Bootstraps the application by registering providers and calling the `boot` method
     * on each provider. Then, it loads the routes from the routes file and adds them to
     * the router. Finally, it dispatches the current request and sends the response.
     *
     * @return void
     */
    public function run(): void
    {
        // Bootstraps the application
        $this->container->bootServiceProviders();

        // Dispatches the request and sends the response
        $this->container
            ->get(Router::class)
            ->dispatch(
                $this->container,
                $this->container->get(Middleware::class),
                $this->container->get(Request::class),
            )
            ->send();
    }
    /**
     * Retrieves a service from the dependency injection container.
     *
     * This is a dynamic getter that allows you to access services from the container.
     * It is equivalent to calling the `get` method on the container.
     *
     * @param string $abstract The abs$abstract of the service to be retrieved.
     * @return mixed The retrieved service.
     */
    public function __get(string $abstract)
    {
        return $this->container->get($abstract);
    }

    /**
     * Registers a service with the dependency injection container.
     *
     * This is a dynamic setter that allows you to register services with the container.
     * It is equivalent to calling the `bind` method on the container.
     *
     * @param string $abstract The abstract name or class name of the service to be registered.
     * @param mixed $concrete The concrete value of the service to be registered.
     * @return void
     */
    public function __set(string $abstract, $concrete = null)
    {
        $this->container->bind($abstract, $concrete);
    }

    /**
     * Checks if a given abstract has a binding in the container.
     *
     * This is a dynamic isset that allows you to check if a service is bound in the container.
     * It is equivalent to calling the `has` method on the container.
     *
     * @param string $name The abstract name or class name of the service to be checked.
     * @return bool True if the abstract has a binding, false otherwise.
     */
    public function __isset(string $name)
    {
        return $this->container->has($name);
    }

    /**
     * Removes a binding from the dependency injection container.
     *
     * This is a dynamic unset that allows you to remove a service from the container.
     * It is equivalent to calling the `forget` method on the container.
     *
     * @param string $name The abstract name or class name of the service to be removed.
     * @return void
     */
    public function __unset(string $name)
    {
        $this->container->forget($name);
    }

    /**
     * Calls a method on the container.
     *
     * This is a dynamic method call that allows you to call any method on the container.
     * It is equivalent to calling the method directly on the container.
     *
     * @param string $method The method to be called on the container.
     * @param array $arguments The arguments to be passed to the method.
     * @return mixed The result of the method call.
     */
    public function __call(string $method, array $arguments)
    {
        return $this->container->{$method}(...$arguments);
    }
}
