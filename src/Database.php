<?php

namespace Hyper;

use Exception;
use PDO;
use PDOStatement;

/**
 * Class Database
 * 
 * Manages database connections and provides query execution and statement preparation.
 * 
 * @package hyper
 * @author Shahin Moyshan <shahin.moyshan2@gmail.com>
 */
class Database
{
    /**
     * Store the PDO connection of database.
     * 
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Database configuration.
     *
     * @var array
     */
    private array $config = [];

    /**
     * Initializes the database connection.
     *
     * @param array $config Database configuration.
     */
    public function __construct(array $config = [])
    {
        // If no configuration is provided, use the default configuration.
        if (empty($config)) {
            $config = env('database');
        }

        $this->config = $config;
    }

    /**
     * Retrieves a configuration value by key.
     *
     * @param string $key The configuration key.
     * @param mixed $default The default value if the configuration key is not found.
     * @return mixed The configuration value.
     */
    public function getConfig(string $key, $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Resets the database configuration.
     *
     * This method resets the database configuration with the given configuration
     * and unsets the PDO connection. It is useful when you want to change the
     * database configuration at runtime.
     *
     * @param array $config The new database configuration.
     * @return static The database instance.
     */
    public function resetConfig(array $config): self
    {
        unset($this->pdo);
        $this->config = $config;
        return $this;
    }

    /**
     * Retrieves or initializes the PDO instance.
     *
     * @return PDO The PDO connection instance.
     */
    public function getPdo(): PDO
    {
        if (!isset($this->pdo)) {
            $this->resetPdo();
        }

        return $this->pdo;
    }

    /**
     * Executes a raw SQL query with optional arguments.
     *
     * @param string $statement The SQL query.
     * @param mixed ...$args Additional arguments for query execution.
     * @return PDOStatement|false The resulting statement or false on failure.
     */
    public function query(string $statement, ...$args): PDOStatement|false
    {
        return $this->getPdo()->query($statement, ...$args);
    }

    /**
     * Prepares an SQL statement for execution with optional options.
     *
     * @param string $statement The SQL query to prepare.
     * @param array $options Options for statement preparation.
     * @return PDOStatement|false The prepared statement or false on failure.
     */
    public function prepare(string $statement, array $options = []): PDOStatement|false
    {
        return $this->getPdo()->prepare($statement, $options);
    }

    /**
     * Handles dynamic method calls, allowing direct PDO method calls on this class.
     *
     * @param string $name The name of the method to call.
     * @param array $args The arguments for the method call.
     * @return mixed The result of the PDO method call.
     */
    public function __call(string $name, array $args)
    {
        return call_user_func_array([$this->getPdo(), $name], $args);
    }

    /**
     * Initializes or resets the PDO connection using the provided configuration.
     *
     * @return self
     */
    public function resetPdo(): self
    {
        // Clear previous PDO connection if exists.
        unset($this->pdo);

        // Check if config is empty.
        if (empty($this->config)) {
            throw new Exception('Database configuration is empty.');
        }

        // Check if config has a default DSN else. create a new one. 
        $dsn = $this->config['dsn'] ?? $this->buildDsn();

        // Merge PDO default options with config.
        $options = array_merge(
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            $this->config['options'] ?? []
        );

        /** 
         * Create a new databse (PHP Data Object) connection.
         * 
         * learn more about pdo and drivers from:
         * @link https://www.php.net/manual/en/book.pdo.php
         */
        $this->pdo = new PDO(
            $dsn,
            $this->config['user'] ?? null,
            $this->config['password'] ?? null,
            $options
        );

        // Enable checking foreign keys for sqlite database.
        if ($this->config['driver'] === 'sqlite') {
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
        }

        return $this;
    }

    /**
     * Builds the DSN (Data Source Name) string based on the configuration settings.
     *
     * @return string The constructed DSN string.
     */
    private function buildDsn(): string
    {
        return match ($this->config['driver']) {
            // create a sqlite data source name, sqlite.db filepath.
            'sqlite' => sprintf('sqlite:%s', $this->config['file']),

            /** create a server side data source name.
             * 
             * supported drivers: mysql, pgsql, cubrid, dblib, firebird, ibm, informix, sqlsrv, oci, odbc
             * @see https://www.php.net/manual/en/pdo.drivers.php
             **/
            default => sprintf(
                "%s:%s%s%s%s",
                $this->config['driver'],
                isset($this->config['host']) ?
                sprintf('host=%s;', $this->config['host']) : '',
                isset($this->config['port']) ?
                sprintf('port=%s;', $this->config['port']) : '',
                isset($this->config['name']) ?
                sprintf('dbname=%s;', $this->config['name']) : '',
                isset($this->config['charset']) ?
                sprintf('charset=%s;', $this->config['charset']) : '',
            ),
        };
    }
}
