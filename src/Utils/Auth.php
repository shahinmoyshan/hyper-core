<?php

namespace Hyper\Utils;

use Hyper\Model;

/**
 * Class Auth
 * 
 * Handles user authentication and authorization for the Hyper framework.
 * This class provides the ability to log users in, log users out, and check if a user is logged in.
 *
 * @package Hyper\Utils
 * @author Shahin Moyshan <shahin.moyshan2@gmail.com>
 */
class Auth
{
    /**
     * @var false|Model The currently logged in user.
     */
    private false|Model $user;

    /**
     * Constructor for the Auth class.
     *
     * Initializes the Auth instance with the specified session management, user model,
     * and optional configuration settings.
     *
     * @param Session $session The session instance used for managing user sessions.
     * @param string $userModel The fully qualified class name of the user model.
     * @param array $config Optional configuration array for customizing session key,
     *                      cache settings, and route redirections.
     */
    public function __construct(private Session $session, private string $userModel, private array $config = [])
    {
        $this->config = array_merge([
            'session_key' => 'admin_user_id',
            'cache_enabled' => true,
            'cache_name' => 'logged_user',
            'cache_expire' => '10 minutes',
            'guest_route' => 'admin.auth.login',
            'logged_in_route' => 'admin.dashboard',
        ], $config);
    }

    /**
     * Gets the currently logged in admin user.
     *
     * If the user is already cached, it will be returned from cache. 
     * Otherwise, it will be fetched from the database and stored in cache 
     * for the specified cache expiry duration.
     *
     * @return false|Model The currently logged in admin user, or false if not found.
     */
    public function getUser(): false|Model
    {
        // Check if the user's ID is not set and the session has the session key
        if (!isset($this->user)) {
            if ($this->session->has($this->config['session_key'])) {
                // Attempt to load user from cache if caching is enabled
                if ($this->config['cache_enabled']) {
                    $this->user = cache($this->config['cache_name'])
                        ->load(
                            key: $this->session->get($this->config['session_key']),
                            callback: fn() => $this->userModel::find(
                                $this->session->get($this->config['session_key'])
                            ),
                            expire: $this->config['cache_expire']
                        );
                    unload_cache($this->config['cache_name']); // Unload cache after use
                } else {
                    // Fetch user directly from the database if caching is not enabled
                    $this->user = $this->userModel::find(
                        $this->session->get($this->config['session_key'])
                    );
                }
            } else {
                $this->user = false;
            }
        }

        // Return the currently logged in user
        return $this->user;
    }

    /**
     * Retrieves the route for guest users.
     *
     * This route is used to redirect users who are not logged in
     * to the appropriate login page or guest access endpoint.
     *
     * @return string The route path for guest users.
     */
    public function getGuestRoute(): string
    {
        return route_url($this->config['guest_route']);
    }

    /**
     * Retrieves the route for logged in users.
     *
     * This route is used to redirect users who are logged in
     * to the appropriate admin dashboard or login success endpoint.
     *
     * @return string The route path for logged in users.
     */
    public function getLoggedInRoute(): string
    {
        return route_url($this->config['logged_in_route']);
    }

    /**
     * Checks if the current user is a guest (not logged in).
     *
     * @return bool True if the user is a guest, false otherwise.
     */
    public function isGuest(): bool
    {
        return $this->getUser() === false;
    }

    /**
     * Logs in the specified user by setting the session and user properties.
     *
     * @param Model $user The user model to be logged in.
     * @return void
     */
    public function login(Model $user): void
    {
        $this->session->set($this->config['session_key'], $user->id);
        $this->user = $user;
    }

    /**
     * Logs out the current user by deleting the session and user properties.
     *
     * @return void
     */
    public function logout(): void
    {
        // Erase the cache for the logged in user.
        $this->clearCache();

        // Delete the session variable and unset the user property.
        $this->session->delete($this->config['session_key']);
        unset($this->user);
    }

    /**
     * Clears the cache for the currently logged in user.
     *
     * This method erases the cached user instance if caching is enabled,
     * using the configured cache name and session key to identify the user.
     *
     * @return void
     */
    public function clearCache(): void
    {
        if ($this->config['cache_enabled']) {
            cache($this->config['cache_name'])
                ->erase($this->session->get($this->config['session_key']))
                ->unload();
        }
    }

    /**
     * Refreshes the user instance if the user is not a guest.
     *
     * If the user is not a guest, this method will refresh the user instance by
     * deleting the user property and calling the getUser method again.
     *
     * @return void
     */
    public function refresh(): void
    {
        if ($this->isGuest()) {
            return;
        }

        $this->clearCache();
        unset($this->user);
        $this->getUser();
    }

    /**
     * Magic getter for the admin user properties.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->getUser()->{$name};
    }

    /**
     * Magic setter for the admin user properties.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->getUser()->{$name} = $value;
    }

    /**
     * Magic isset for the admin user properties.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return isset($this->getUser()->{$name});
    }

    /**
     * Magic method call for the admin user methods.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return $this->getUser()->{$method}(...$args);
    }
}
