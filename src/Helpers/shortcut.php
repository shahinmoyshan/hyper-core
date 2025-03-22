<?php

use Hyper\Application;
use Hyper\Container;
use Hyper\Database;
use Hyper\Helpers\Vite;
use Hyper\Query;
use Hyper\Request;
use Hyper\Response;
use Hyper\Router;
use Hyper\Template;
use Hyper\Translator;
use Hyper\Utils\Auth;
use Hyper\Utils\Cache;
use Hyper\Utils\Collect;
use Hyper\Utils\Session;
use Hyper\Utils\Sanitizer;
use Hyper\Utils\Validator;

/**
 * Retrieve the application instance.
 *
 * This function returns the application instance, which is the top-level class
 * responsible for managing the application's lifecycle.
 *
 * @param string $abstract [optional] The abstract name or class name of the service or value to retrieve.
 *                          If not provided, the application instance is returned.
 *
 * @return Application The application instance or the resolved instance of the specified class or interface.
 */
function app(?string $abstract = null): Application
{
    if ($abstract !== null) {
        return get($abstract);
    }

    return Application::$app;
}

/**
 * Retrieve the application's dependency injection container.
 *
 * @return Container The dependency injection container instance.
 */
function container(): Container
{
    return app()->getContainer();
}

/**
 * Retrieve an instance of the given class or interface from the application container.
 * 
 * This function resolves and returns the instance of the specified class or interface
 * abstract from the application's dependency injection container.
 * 
 * @param string $abstract The class or interface name to resolve.
 * 
 * @return mixed The resolved instance of the specified class or interface.
 */
function get(string $abstract)
{
    return app()->get($abstract);
}

/**
 * Checks if a given abstract has a binding in the container.
 *
 * @param string $abstract The abstract name or class name of the service or value to be checked.
 * 
 * @return bool True if the abstract has a binding, false otherwise.
 */
function has(string $abstract): bool
{
    return app()->has($abstract);
}

/**
 * Registers a service provider with the application's dependency injection container.
 *
 * Bindings are registered with the container and returned on each request.
 *
 * @param string $abstract The abstract name or class name of the service to be resolved.
 * @param mixed $concrete The concrete value of the service to be resolved.
 */
function bind(string $abstract, $concrete = null): void
{
    app()->bind($abstract, $concrete);
}

/**
 * Registers a singleton service provider with the application's dependency injection container.
 *
 * Singleton bindings are registered with the container and returned on each request.
 * Once a singleton binding is registered, the same instance will be returned on each subsequent request.
 *
 * @param string $abstract The abstract name or class name of the service to be resolved.
 * @param mixed $concrete The concrete value of the service to be resolved.
 */
function singleton(string $abstract, $concrete = null): void
{
    app()->singleton($abstract, $concrete);
}

/**
 * Get the current request instance.
 *
 * @param string|string[] $key The key to retrieve from the request data.
 * @param mixed $default The default value to return if the key does not exist.
 *
 * @return Request|mixed The current request instance or the value of the specified key from the request data.
 */
function request($key = null, $default = null): mixed
{
    if ($key !== null) {
        // Retrieve the request input as an array.
        $input = get(Request::class)->all((array) $key);

        if (is_string($key)) {
            // Return the value of the specified key if it exists, otherwise the default.
            return $input[$key] ?? $default;
        }

        // Return the entire request input array.
        return $input;
    }

    // Return the current request instance.
    return get(Request::class);
}

/**
 * Get the current response instance or create a new one with provided data.
 *
 * This function returns the current response instance. If arguments are provided,
 * it creates a new response instance with those arguments.
 *
 * @param mixed $args Optional arguments to create a new response instance.
 * @return Response The response instance.
 */
function response(...$args): Response
{
    if (!empty($args)) {
        // Create and return a new Response with the provided arguments.
        return new Response(...$args);
    }

    // Return the existing Response instance from the container.
    return get(Response::class);
}

/**
 * Redirect to a specified URL.
 *
 * @param string $url The URL to redirect to.
 * @param bool $replace Whether to replace the current header. Default is true.
 * @param int $httpCode The HTTP status code for the redirection. Default is 0.
 */
function redirect(string $url, bool $replace = true, int $httpCode = 0): void
{
    response()->redirect($url, $replace, $httpCode);
}

/**
 * Manage session data by setting or retrieving values.
 *
 * This function can be used to set multiple session variables by passing an associative array,
 * or to retrieve a single session value by passing a string key.
 *
 * @param array|string|null $param An associative array for setting session data, a string key for retrieving a value, or null to return the session instance.
 * @param mixed $default The default value to return if the key does not exist.
 * @return Session|mixed The session instance, the value of the specified key, or the default value if the key does not exist.
 */
function session($param = null, $default = null): mixed
{
    $session = get(Session::class);

    if (is_array($param)) {
        // Set multiple session variables from the associative array.
        foreach ($param as $key => $value) {
            $session->set($key, $value);
        }
    } elseif ($param !== null) {
        // Retrieve a single session value by key.
        return $session->get($param, $default);
    }

    // Return the session instance.
    return $session;
}

/**
 * Get the current router instance.
 *
 * @return Router
 */
function router(): Router
{
    return get(Router::class);
}

/**
 * Get the current database instance.
 *
 * @return Database The database instance.
 */
function database(): Database
{
    return get(Database::class);
}

/**
 * Create a new query instance.
 *
 * @param string $table The name of the table.
 *
 * @return Query The query instance.
 */
function query(string $table): Query
{
    return get(Query::class)
        ->table($table);
}

/**
 * Render a template and return the response.
 *
 * @param string $template
 * @param array $context
 * @return Response
 */
function template(string $template, array $context = []): Response
{
    return get(Response::class)->write(
        get(Template::class)
            ->render($template, $context)
    );
}

/**
 * Check if a template exists.
 *
 * @param string $template The name of the template file, without the .php extension.
 *
 * @return bool True if the template exists, false otherwise.
 */
function template_exists(string $template): bool
{
    return file_exists(
        dir_path(env('template_dir') . '/' . str_replace('.php', '', $template) . '.php')
    );
}

/**
 * Generate a URL from a given path.
 *
 * The path can be relative or absolute. If it is relative, it will be
 * resolved relative to the root URL of the application. If it is absolute,
 * it will be returned verbatim.
 *
 * @param string $path The path to generate a URL for.
 *
 * @return string The generated URL.
 */
function url(string $path = ''): string
{
    return rtrim(request()->getRootUrl() . '/' . ltrim(str_replace('\\', '/', $path), '/'), '/');
}

/**
 * Generate a URL from a given path relative to the asset directory.
 *
 * The path can be relative or absolute. If it is relative, it will be
 * resolved relative to the asset directory. If it is absolute,
 * it will be returned verbatim.
 *
 * @param string $path The path to generate a URL for.
 *
 * @return string The generated URL.
 */
function asset_url(string $path = ''): string
{
    $path = env('asset_url') . ltrim($path, '/');
    return strpos($path, '/', 0) === 0 ? url($path) : $path;
}

/**
 * Generate a URL from a given path relative to the media directory.
 *
 * The path can be relative or absolute. If it is relative, it will be
 * resolved relative to the media directory. If it is absolute,
 * it will be returned verbatim.
 *
 * @param string $path The path to generate a URL for.
 *
 * @return string The generated URL.
 */
function media_url(string $path = ''): string
{
    $path = env('media_url') . ltrim($path, '/');
    return strpos($path, '/', 0) === 0 ? url($path) : $path;
}

/**
 * Get the URL of the current request.
 *
 * @return string The URL of the current request.
 */
function request_url(): string
{
    return request()->getUrl();
}

/**
 * Generate a URL for a named route with an optional context.
 *
 * This function constructs a URL for a given named route, optionally
 * including additional context. The route name is resolved using the
 * application's router.
 *
 * @param string $name The name of the route to generate a URL for.
 * @param null|string|array $context Optional context to include in the route.
 *
 * @return string The generated URL for the specified route.
 */
function route_url(string $name, null|string|array $context = null): string
{
    return url(router()->route($name, $context));
}

/**
 * Get the application directory path with an optional appended path.
 *
 * This function returns the application's root directory path, optionally
 * appending a specified sub-path to it. The resulting path is normalized
 * with a single trailing slash.
 *
 * @param string $path The sub-path to append to the application directory path. Default is '/'.
 *
 * @return string The full path to the application directory, including the appended sub-path.
 */
function root_dir(string $path = '/'): string
{
    return dir_path(app()->getPath() . '/' . ltrim($path, '/'));
}

/**
 * Get the application storage directory path with an optional appended path.
 *
 * This function returns the application's storage directory path, optionally
 * appending a specified sub-path to it. The resulting path is normalized
 * with a single trailing slash.
 *
 * @param string $path The sub-path to append to the storage directory path. Default is '/'.
 *
 * @return string The full path to the storage directory, including the appended sub-path.
 */
function storage_dir(string $path = '/'): string
{
    return dir_path(env('storage_dir') . '/' . ltrim($path, '/'));
}

/**
 * Get the upload directory path with an optional appended path.
 *
 * This function returns the upload directory path, optionally appending a
 * specified sub-path to it. The resulting path is normalized with a single
 * trailing slash.
 *
 * @param string $path The sub-path to append to the upload directory path. Default is '/'.
 *
 * @return string The full path to the upload directory, including the appended sub-path.
 */
function upload_dir(string $path = '/'): string
{
    return dir_path(env('upload_dir') . '/' . ltrim($path, '/'));
}

/**
 * Returns the path to the given directory.
 *
 * This function takes a given path and returns the path to the directory
 * represented by that path. The path is trimmed of any trailing slashes and
 * the directory separator is normalized to the correct separator for the
 * current platform.
 *
 * @param string $path The path to the directory.
 * @return string The path to the directory.
 */
function dir_path(string $path): string
{
    return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
}

// Helper/Utils Shortcut

/**
 * Dump the given variable(s) with syntax highlighting.
 *
 * @param mixed ...$args The variable(s) to dump.
 *
 * @return void
 */
function dump(...$args)
{
    if (php_sapi_name() !== 'cli') {
        echo '<pre style="font-size: 18px;margin: 25px;"><code>';
        ob_start();

        // Dump the given variable(s) to the output
        var_dump(...$args);

        // Get the output from the output buffer
        $output = highlight_string('<?php ' . ob_get_clean(), true);

        // Remove the <?php tag from the output
        echo str_replace('&lt;?php ', '', $output);

        // Close the <pre> and <code> tags
        echo '</code></pre>';
    } else {
        var_dump(...$args);
    }
}

/**
 * Dump the given variable(s) with syntax highlighting and die.
 *
 * @param mixed ...$args The variable(s) to dump.
 *
 * @return never
 */
function dd(...$args): never
{
    dump(...$args);
    die(0);
}

/**
 * Get the value of the specified environment variable.
 *
 * This function returns the value of the specified environment variable. If
 * the variable is not set, the given default value is returned instead.
 *
 * @param string $key The name of the environment variable to retrieve.
 * @param mixed $default The default value to return if the variable is not set.
 *
 * @return mixed The value of the specified environment variable, or the default
 * value if it is not set.
 */
function env(string $key, $default = null): mixed
{
    return app()->getEnv($key, $default);
}

/**
 * Get the CSRF token.
 *
 * This function returns the CSRF token as a string. The CSRF token is a
 * random string that is generated when the application is booted. The CSRF
 * token is used to protect against cross-site request forgery attacks.
 *
 * @return string|null The CSRF token, or null if no token has been generated yet.
 */
function csrf_token(): ?string
{
    $token = cookie('csrf_token');

    // Generate a new token if it doesn't exist
    if (empty($token)) {
        $token = bin2hex(random_bytes(32));
        cookie([
            'csrf_token',
            $token,
            [
                'expires' => time() + 21600,  // Expire in 6 hours
                'path' => '/',           // Available site-wide
                'domain' => '',            // Default to current domain
                'secure' => true,          // Only send over HTTPS
                'httponly' => false,         // Must be false so JavaScript can read it
                'samesite' => 'Strict'       // Prevent cross-site requests
            ]
        ]);
    }

    // Return the token
    return $token;
}

/**
 * Generates a hidden form field containing the CSRF token.
 *
 * This function returns a string containing an HTML input field with the name
 * "_token" and the value of the CSRF token. The CSRF token is a random string
 * that is generated when the application is booted. It is used to protect
 * against cross-site request forgery attacks.
 *
 * @return string A string containing the CSRF token as a hidden form field.
 */
function csrf(): string
{
    return sprintf('<input type="hidden" name="_token" value="%s" />', csrf_token());
}

/**
 * Generates a hidden form field containing the specified HTTP method.
 *
 * This function returns a string containing an HTML input field with the name
 * "_method" and the value of the specified HTTP method. The resulting string
 * can be used in a form to simulate a different HTTP method than the one
 * specified in the form's "method" attribute.
 *
 * @param string $method The HTTP method to simulate.
 *
 * @return string A string containing the HTTP method as a hidden form field.
 */
function method(string $method): string
{
    return sprintf('<input type="hidden" name="_method" value="%s" />', strtoupper($method));
}

/**
 * Get the application's authentication manager.
 *
 * This function returns the application's authentication manager instance.
 * The authentication manager is responsible for authenticating users, and
 * managing the currently authenticated user.
 *
 * @return Auth The application's authentication manager.
 */
function auth(): Auth
{
    return get(Auth::class);
}

/**
 * Determine if the current request is made by a guest user.
 *
 * This function checks if the user is not set in the current application request,
 * indicating that the request is made by a guest (unauthenticated) user.
 *
 * @return bool True if the request is made by a guest user, false otherwise.
 */
function is_guest(): bool
{
    return auth()->isGuest();
}

/**
 * Get the currently authenticated user.
 *
 * If no user is authenticated, this function returns null. If a key is provided,
 * this function will return the value of the provided key from the user's data.
 * If the key does not exist in the user's data, the default value will be returned
 * instead.
 *
 * @param string $key The key to retrieve from the user's data.
 * @param mixed $default The default value to return if the key does not exist.
 *
 * @return mixed The user object, or the value of the provided key from the user's data.
 */
function user(?string $key = null, $default = null): mixed
{
    return $key !== null ? (auth()->getUser()->{$key} ?? $default) : auth()->getUser();
}

/**
 * Create a new collection instance.
 *
 * This function initializes a new collection object containing the given items.
 * The collection can be used to manipulate and interact with the array of items
 * using various collection methods.
 *
 * @param array $items The array of items to include in the collection.
 *
 * @return Collect A collection instance containing the provided items.
 */
function collect(array $items = []): Collect
{
    return get(Collect::class)->make($items);
}

/**
 * Retrieve or create a cache instance by name.
 *
 * This function returns an existing cache instance by the given name,
 * or creates a new one if it doesn't already exist. Cache instances
 * are stored globally and can be accessed using their names.
 *
 * @param string $name The name of the cache instance to retrieve or create. Default is 'default'.
 * @return Cache The cache instance associated with the specified name.
 */
function cache(string $name = 'default'): Cache
{
    global $caches;

    if (!isset($caches[$name])) {
        $caches[$name] = get(Cache::class)->setName($name);
    }

    return $caches[$name];
}

/**
 * Unloads a cache instance.
 *
 * This function unloads a cache instance by name. Once unloaded, the cache
 * will be removed from the global cache list and cannot be accessed again.
 *
 * @param string $name The name of the cache instance to unload. Default is 'default'.
 */
function unload_cache(string $name = 'default'): void
{
    global $caches;
    if (isset($caches[$name])) {
        $caches[$name]->unload();
        unset($caches[$name]);
    }
}

/**
 * Translates a given text using the application's translator service.
 *
 * This function wraps the translator's `translate` method, allowing
 * for text translation with optional pluralization and argument substitution.
 *
 * @param string $text The text to be translated.
 * @param $arg The number to determine pluralization or replace placeholder in the translated text.
 * @param array $args Optional arguments for replacing placeholders in the text.
 * @param array $args2 Optional arguments for replacing plural placeholders in the translated text.
 * 
 * @return string The translated text or original text if translation is unavailable.
 */
function __(string $text, $arg = null, array $args = [], array $args2 = []): string
{
    return get(Translator::class)
        ->translate($text, $arg, $args, $args2);
}

/**
 * Create a new Vite instance.
 *
 * This function initializes a new Vite instance with the given configuration.
 * The Vite instance provides a convenient interface for interacting with the
 * development server and production build processes.
 *
 * @param array $config The configuration for the Vite instance.
 *
 * @return Vite The Vite instance initialized with the given configuration.
 */
function vite($config): Vite
{
    return get(Vite::class)
        ->configure($config);
}

/**
 * Retrieve and sanitize input data from the current request.
 *
 * This function fetches the input data from the current request and applies
 * the specified filter. The data is then passed through a sanitizer to ensure
 * it is safe for further processing.
 *
 * @param array $filter An optional array of filters to apply to the input data.
 * @return Sanitizer An instance of the sanitizer containing the sanitized input data.
 */
function input(array $filter = []): Sanitizer
{
    return get(Sanitizer::class)
        ->setData(
            request()->all($filter)
        );
}

/**
 * Validates the given data against a set of rules.
 *
 * @param array $rules An array of validation rules to apply.
 * @param array|null $data An optional array of data to validate.
 * @return Sanitizer Returns a sanitizer object if validation passes.
 * @throws Exception Throws an exception if validation fails, with the first error message or a default message.
 */
function validator(array $rules, ?array $data = null): Sanitizer
{
    $data ??= request()->all();

    $validator = get(Validator::class);
    $result = $validator->validate($rules, $data);

    if ($result) {
        return get(Sanitizer::class)
            ->setData($result);
    }

    throw new Exception($validator->getFirstError() ?? 'validation failed');
}

/**
 * Escapes a string for safe output in HTML by converting special characters to HTML entities.
 *
 * @param null|string $text The string to be escaped.
 * @return ?string The escaped string, safe for HTML output.
 */
function _e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Translates and escapes a given text for safe HTML output.
 *
 * This function first translates the provided text using the application's
 * translation service, with optional pluralization and argument substitution.
 * The translated text is then escaped to ensure it is safe for rendering
 * in HTML, converting special characters to HTML entities.
 *
 * @param string $text The text to be translated and escaped.
 * @param mixed $arg An optional argument for pluralization or placeholder replacement.
 * @param array $args Optional arguments for replacing placeholders in the text.
 * @param array $args2 Optional arguments for replacing plural placeholders in the translated text.
 *
 * @return string The translated and escaped text, safe for HTML output.
 */
function __e(string $text, $arg = null, array $args = [], array $args2 = []): string
{
    return _e(
        __($text, $arg, $args, $args2)
    );
}

/**
 * Retrieve or set a cookie value.
 *
 * This function allows you to either retrieve the value of a cookie by name,
 * or set a new cookie using an array of parameters. When setting a cookie,
 * the parameters should be passed in an array format compatible with setcookie().
 *
 * @param array|string $param The name of the cookie to retrieve, or an array of parameters to set a cookie.
 * @param mixed $default The default value to return if the cookie is not set and a string name is provided.
 * @return mixed The value of the cookie if retrieving, or the result of setcookie() if setting.
 */
function cookie(array|string $param, $default = null): mixed
{
    // Check if setting a cookie
    if (is_array($param)) {
        $values = array_values($param);
        $_COOKIE[$values[0]] = $values[1];

        // Set the cookie using the provided parameters
        return setcookie(...$param);
    }

    // Retrieve the cookie value or return the default value if not set
    return $_COOKIE[$param] ?? $default;
}
