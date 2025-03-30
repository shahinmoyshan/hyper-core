<?php

namespace Hyper;

use Exception;
use PDO;

/**
 * Class Model
 *
 * This class provides a base for models, handling database operations and entity management.
 * It includes CRUD operations, data decoding, and dynamic method invocation.
 * 
 * @package hyper
 * @author Shahin Moyshan <shahin.moyshan2@gmail.com>
 */
abstract class Model
{
    /**
     * @var string The table name associated with this model.
     */
    public static string $table;

    /**
     * The primary key of the model.
     *
     * @var string Default value: 'id'
     */
    public static string $primaryKey = 'id';

    /**
     * The model attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * model constructor.
     * Initializes the model instance and decodes any previously saved data if an ID is set.
     */
    public function __construct()
    {
        if ($this->primaryValue()) {
            $this->decodeSavedData();
        }
    }

    /**
     * Creates a new query instance for the model's table.
     *
     * @return Query Returns a query object for database operations.
     */
    public static function query(): Query
    {
        // Return a new database query builder object.
        return get(Query::class)->table(static::$table);
    }

    /**
     * Gets a new query object with SELECT applied and ready for fetching.
     *
     * @return Query A query object set to fetch data as instances of the current model.
     */
    public static function get(): Query
    {
        return self::query()
            ->select()
            ->fetch(PDO::FETCH_CLASS, static::class);
    }

    /**
     * Finds a model by its primary key ID.
     *
     * @param int $value The Unique Identifier of the model to retrieve.
     * @return false|static The found model instance or false if not found.
     */
    public static function find($value): false|static
    {
        return self::get()
            ->where([static::$primaryKey => $value])
            ->first();
    }

    /**
     * Loads an array of data into a new model instance.
     *
     * @param array $data Key-value pairs of model properties.
     * @return static A model instance populated with the given data.
     */
    public static function load(array $data): static
    {
        // Create & Hold a new model.
        $model = new static();

        foreach ($data as $key => $value) {
            $model->$key = $value;
        }

        // Decode model properties from JSON to array if necessary.
        $model->decodeSavedData();

        // Return the new model object.
        return $model;
    }

    /**
     * Saves the model to the database, either updating or creating a new entry.
     *
     * @return int|bool The ID of the saved model or false on failure.
     */
    public function save(): int|bool
    {
        // Apply events for before save and encode array into json string. 
        $data = $this->beforeSaveData($this->getInsertableData());

        // Update this records if it has an id, else insert this records into database.
        if (!empty($this->primaryValue())) {
            $status = $this->query()->update($data, [static::$primaryKey => $this->primaryValue()]);
        } else {
            $status = $this->query()->insert($data);
        }

        // Save model id if it is newly created.
        if (is_int($status)) {
            $this->{static::$primaryKey} = $status;
        }

        // Apply model events after saved the record.
        $this->decodeSavedData();
        $event_status = $this->afterSavedData();

        if (!$status && $event_status) {
            $status = true;
        }

        // Return database operation status.
        return $status;
    }

    /**
     * Removes the model from the database.
     *
     * @return bool True if removal was successful, false otherwise.
     */
    public function remove(): bool
    {
        // Call events when this model is about to be deleted.
        $this->beforeRemoveData();

        // Remove this record from database.
        $removed = $this->query()->delete([static::$primaryKey => $this->primaryValue()]);

        // Call events when this model is deleted.
        if ($removed) {
            $this->afterRemovedData();
        }

        // Returns database operation status.
        return $removed;
    }

    /**
     * Retrieves the fillable fields of the model based on the $fillable and $guarded properties.
     *
     * If $fillable is defined, only the fields specified in the array are included unless
     * explicitly restricted by the $guarded property. If $fillable is empty, all fields
     * are included except the ones that are explicitly guarded by the $guarded property.
     *
     * @return array The fillable fields of the model.
     */
    private function getInsertableData(): array
    {
        $data = [];

        $fillable = $this->fillable ?? [];
        $guarded = $this->guarded ?? [];

        if (!isset($this->fillable) && !isset($this->guarded)) {
            throw new Exception('Either fillable or guarded must be defined for the modal: ' . static::class);
        }

        foreach ($this->attributes as $key => $value) {
            // If fillable is defined, only allow fillable fields unless guarded explicitly restricts it.
            if (!empty($fillable) && in_array($key, $fillable)) {
                $data[$key] = $value;
            }
            // If fillable is empty, assume all fields are fillable except the guarded ones.
            elseif (empty($fillable) && !in_array($key, $guarded)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Prepares data before saving, such as encoding arrays to JSON.
     *
     * @param array $data The data to prepare.
     * @return array The prepared data for database insertion or update.
     */
    private function beforeSaveData(array $data): array
    {
        // Call beforeSave event
        if (method_exists($this, 'beforeSave')) {
            $data = $this->beforeSave($data);
        }

        // Check uploaded files, if model has files enabled.
        if (method_exists($this, 'uploadChanges')) {
            $data = $this->uploadChanges($data);
        }

        // Parse model property into string if the are in array.
        foreach ($data as $key => $value) {
            $data[$key] = is_array($value) ? json_encode($value) : $value;
        }

        // returns associative array of model properties.
        return $data;
    }

    /**
     * Callback after saving data to handle post-save tasks.
     * 
     * @return bool
     */
    private function afterSavedData(): bool
    {
        $status = false;

        // Call afterSave event
        if (method_exists($this, 'afterSave')) {
            $this->afterSave();
        }

        // Check ORM form fields changes 
        if (method_exists($this, 'checkOrmFormFields')) {
            $status = $this->checkOrmFormFields();
        }

        return $status;
    }

    /**
     * Called before removing the model from the database.
     * 
     * @return void
     */
    private function beforeRemoveData(): void
    {
        // Call beforeRemove event
        if (method_exists($this, 'beforeRemove')) {
            $this->beforeRemove();
        }
    }

    /**
     * Callback after removing data to handle post-remove tasks.
     * 
     * @return void
     */
    private function afterRemovedData(): void
    {
        // Call afterRemove event
        if (method_exists($this, 'afterRemove')) {
            $this->afterRemove();
        }

        // Removed uploaded files wich are associated with this model
        if (method_exists($this, 'removeUploaded')) {
            $this->removeUploaded($this->attributes);
        }
    }

    /**
     * Decodes JSON strings in properties to their original formats.
     * 
     * @return void
     */
    private function decodeSavedData(): void
    {
        // Go Through all the properties of this model.
        foreach ($this->attributes as $key => $value) {
            /** 
             * if the property is json format then decode json
             * string to associative array.
             * 
             * if property is a array then replace it to model.
             */
            if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
                $value = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    /**
     * Gets the value of the primary key.
     *
     * @param mixed $default The value to return if the primary key is empty.
     * @return mixed The value of the primary key or the default value.
     */
    public function primaryValue($default = null): mixed
    {
        return $this->{static::$primaryKey} ?? $default;
    }

    /**
     * Magic setter method to set the value of a model attribute.
     *
     * @param string $name The name of the attribute to set.
     * @param mixed $value The value to set.
     * @return void
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Magic getter method to retrieve the value of a model attribute.
     *
     * @param string $name The name of the attribute to retrieve.
     * @return mixed|null The value of the attribute if set, or null if not set.
     */
    public function __get($name)
    {
        if (!isset($this->attributes[$name]) && method_exists($this, 'getFromOrm')) {
            return $this->getFromOrm($name);
        }

        return $this->attributes[$name] ?? null;
    }

    /**
     * Checks if the specified model attribute is set.
     *
     * @param string $name The name of the attribute to check.
     * @return bool True if the attribute is set, false otherwise.
     */
    public function __isset($name)
    {
        if (isset($this->attributes[$name])) {
            return true;
        } elseif (method_exists($this, 'existsInOrm')) {
            return $this->existsInOrm($name);
        }

        return false;
    }

    /**
     * Magic unset method to remove the specified model attribute.
     *
     * @param string $name The name of the attribute to remove.
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        } elseif (method_exists($this, 'removeFromOrm')) {
            $this->removeFromOrm($name);
        }
    }

    /**
     * Converts the model to an associative array.
     *
     * @return array An array of model properties and their values.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Handles dynamic method calls to the query instance.
     *
     * @param string $name The method name.
     * @param array $arguments The method arguments.
     * @return mixed The result of the query method call.
     */
    public function __call($name, $arguments)
    {
        return call_user_func([$this->get(), $name], ...$arguments);
    }

    /**
     * Handles static method calls to the query instance.
     *
     * @param string $name The method name.
     * @param array $arguments The method arguments.
     * @return mixed The result of the query method call.
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func([self::get(), $name], ...$arguments);
    }

    /**
     * Converts the model to a string representation.
     *
     * @return string A string representation of the model instance.
     */
    public function __toString()
    {
        return sprintf('model: (%s), %s(%d)', static::class, static::$table, $this->primaryValue('#'));
    }
}
