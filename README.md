# HyperCore
Core Classes and Functionalities for Hyper MVT Framework

## Introduction

**HyperCore** is the backbone of the Hyper MVT Framework, providing essential core classes, utility functions, and helper methods to streamline web development. This document details all available classes and functions within the HyperCore.

## Core Classes

### Application
- **Class:** `Application`
- **Description:** Application container that manages the overall lifecycle of your Application.

### Container
- **Class:** `Container`
- **Description:** Simple IoC container for resolving dependencies.

### Database
- **Class:** `Database`
- **Description:** PDO Database container for managing database connections and operations.
- **Example:**
    ```php
    use Hyper\Database;
    $database = new Database([
        // sqlite, mysql, pgsql, cubrid, dblib, firebird, ibm, informix, sqlsrv, oci, odbc
        'driver' => 'sqlite',

        // required when driver is sqlite
        'file' => __DIR__ . '/../sqlite.db',

        // pre-define a custom dsn for pdo else, it will auto genere a dsn from this config array.
        'dsn' => null,

        // define your custom pdo options. Note: PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION added as default.
        'options' => [],

        // when driver is serverside
        'host' => 'localhost',
        'user' => '{user}',
        'password' => '{password}',
        'port' => 3306,
        'name' => '{database name}',
        'charset' => 'utf8mb4'

        // Learn more: https://www.php.net/manual/en/book.pdo.php
    ]);
    dump($database->prepare('SELECT * FROM students'));
    ```

### Request
- **Class:** `Request`
- **Description:** HTTP request class for handling and processing incoming requests.

### Response
- **Class:** `Response`
- **Description:** HTTP response class for managing outgoing responses.

### Middleware
- **Class:** `Middleware`
- **Description:** Middleware class for handling HTTP request routing.

### Router
- **Class:** `Router`
- **Description:** Router class for defining and handling application routes.

### Model
- **Class:** `Model`
- **Description:** Model class for handling database interactions with ORM and form handling.
- **Example:**
    ```php
    use Hyper\Model;

    class Student extends Model {
        protected string $table = 'students';

        public string $name;
        public int $age;
        public string $department;
    }

    dump(Student::get()->result());
    ```

### Query
- **Class:** `Query`
- **Description:** PHP PDO query builder for constructing and executing database queries.

### Session
- **Class:** `Session`
- **Description:** Session class for managing user sessions.

### Template
- **Class:** `Template`
- **Description:** Template engine class for rendering views.

### Translator
- **Class:** `Translator`
- **Description:** Translator helper class to retrieve translated text.

## Utility Classes

### Auth
- **Class:** `Auth`
- **Description:** Handles user authentication and authorization for the Hyper framework.

### Cache
- **Class:** `Cache`
- **Description:** Cache class for caching data.

### Collect
- **Class:** `Collect`
- **Description:** Collection class for handling arrays.

### Hash
- **Class:** `Hash`
- **Description:** Class for hashing and encryption.

### Image
- **Class:** `Image`
- **Description:** Image helper class for managing image operations.
- **Example:**
    ```php
    use Hyper\Utils\Image;
    $image = new Image(__DIR__ . '/image.png');
    $image->compress(50);
    $image->resize(720, 360);
    $image->rotate(90);
    $image->bulkResize([540 => 540, 60 => 60]);
    ```

### Paginator
- **Class:** `Paginator`
- **Description:** Paginator class for handling pagination.
- **Example:**
    ```php
    use Hyper\Utils\Paginator;
    $paginator = new Paginator(total: 500, limit: 20);
    $paginator->setData([...]);
    dump($paginator->getData(), $paginator->getLinks());
    ```

### Ping
- **Class:** `Ping`
- **Description:** HTTP ping/cURL helper class.
- **Example:**
    ```php
    use Hyper\Utils\Ping;
    $http = new Ping();
    $http->download(__DIR__.'/downloads/file.jpg');
    dump($http->get('http://domain.com/download-file'));
    ```

### Uploader
- **Class:** `Uploader`
- **Description:** File uploader class for managing file uploads.
- **Example:**
    ```php
    use Hyper\Utils\Uploader;
    $uploader = new Uploader(uploadDir: __DIR__ .'/uploads', extensions: ['jpe', 'png'], multiple: true);
    dump($uploader->upload($_FILES['upload']));
    ```

### Sanitizer
- **Class:** `Sanitizer`
- **Description:** Sanitizer class provides methods to sanitize and validate different data types.
- **Example:**
    ```php
    use Hyper\Utils\Sanitizer;
    $sanitizer = new Sanitizer(['student_email' => 'hello@mail.me', 'student_age' => '24']);
    dump($sanitizer->email('student_email'), $sanitizer->number('student_age'));
    ```

### Validator
- **Class:** `Validator`
- **Description:** HTTP input validator class.
- **Example:**
    ```php
    use Hyper\Utils\Validator;
    $validator = new Validator();
    dump($validator->validate([
        'name' => ['required', 'min:10', 'max:60'],
        'email' => ['required', 'email'],
    ], $request->all()));
    ```

## Helper Classes

### Mail
- **Class:** `Mail`
- **Description:** PHP built-in mail class.
- **Example:**
    ```php
    use Hyper\Helpers\Mail;
    $mail = new Mail();
    $mail->address('john.doe@main.com', 'John Doe');
    $mail->replyTo('shahin.moyshan2@gmail.com');
    $mail->subject('Test Mail');
    $mail->body('Hello World, This is Test Mail From Shahin Moyshan');
    $mail->send();
    ```

### ORM
- **Class:** `Orm`
- **Description:** Object-Relational Mapper for database interactions.
- **Example:**
    ```php
    use Hyper\Model;
    use Hyper\Helpers\Orm;

    class Student extends Model {
        use Orm;

        protected function orm(): array {
            return [
                'department' => ['has' => 'one', 'model' => Department::class, 'lazy' => false],
                'subjects' => ['has' => 'many-x', 'model' => Subject::class, 'table' => 'students_subjects'],
                'results' => ['has' => 'many', 'model' => Result::class],
            ];
        }
    }

    dump(Student::with(['subjects', 'results', 'department'])->paginate(20));
    ```

### Uploader
- **Class:** `Uploader`
- **Description:** Uploader helper for managing model uploads.
- **Example:**
    ```php
    use Hyper\Model;
    use Hyper\Helpers\Uploader;

    class Student extends Model {
        use Uploader;

        protected function uploader(): array {
            return [
                [
                    'name' => 'photo',
                    'multiple' => false,
                    'uploadTo' => 'students', // sub directory
                    'maxSize' => 1048576, // 1MB
                    'compress' => 75,
                    'resize' => [540 => 540], // resize main uploaded image
                    'resizes' => [140 => 140, 60 => 60], // create new resized images
                ]
            ];
        }
    }
    ```

### Vite
- **Class:** `Vite`
- **Description:** Vite helper class for asset management.

## Shortcut Functions

### Application
- **Function:** `app()`
- **Description:** Returns the application instance.

### Container
- **Function:** `container()`
- **Description:** Returns the Container instance.
- **Functions:**
  - `get(string $abstract): mixed` - Retrieve an instance of the given class or interface from the application container.
  - `has(string $abstract): bool` - Checks if a given abstract has a binding in the container.
  - `bind(string $abstract, $concrete = null): void` - Registers a service provider with the application's dependency injection container.
  - `singleton(string $abstract, $concrete = null): void` - Registers a singleton service provider with the application's dependency injection container.

### Request
- **Function:** `request()`
- **Description:** Returns the request instance.

### Response
- **Function:** `response()`
- **Description:** Returns the response instance.

### Redirect
- **Function:** `redirect()`
- **Description:** Redirects to a different URL and returns void.

### Session
- **Function:** `session()`
- **Description:** Returns the session instance.

### Router
- **Function:** `router()`
- **Description:** Returns the router instance.

### Input
- **Functions:**
  - `validator()` - Validates the given data from request.
  - `input()` - Retrieve and sanitize input data.

### Database
- **Function:** `database()`
- **Description:** Returns the database instance.

### Query
- **Function:** `query()`
- **Description:** Returns the query builder instance.

### Template
- **Function:** `template()`
- **Description:** Returns a new template instance.

### URLs
- **Functions:**
  - `url(string $path = ''): string` - Returns the URL for the given path.
  - `public_url(string $path = ''): string` - Returns the public URL for the given path.
  - `asset_url(string $path = ''): string` - Returns the asset URL for the given path.
  - `media_url(string $path = ''): string` - Returns the media URL for the given path.
  - `request_url()` - Returns the current requested URL.
  - `route_url()` - Returns the route URL.

### Directories
- **Functions:**
  - `app_dir()` - Returns the application directory.
  - `root_dir()` - Returns the root directory.

### Debugging
- **Functions:**
  - `dump()` - Dumps data for inspection.
  - `dd()` - Dumps data and stops execution.

### Environment
- **Function:** `env()`
- **Description:** Gets application environment variable data.

### CSRF & Custom Method
- **Functions:**
  - `csrf_token()` - Returns the CSRF token.
  - `csrf()` - Returns the CSRF token with an HTML hidden input.
  - `method()` - Generates a hidden form field containing the specified HTTP method.

### Auth
- **Function:**
    - `auth()` - Returns the logged-in user.
    - `user()` - Returns the logged-in user.
    - `is_quest()` - Determine if a user logged-in or not.

### Collections
- **Function:** `collect()`
- **Description:** Returns a new collect class instance.

### Cache
- **Function:** `cache()`
- **Description:** Returns a new cache class instance.

### Translation
- **Function:** `__()`
- **Description:** Translates text.

### Vite
- **Function:** `vite()`
- **Description:** Returns a new vite class instance.

### Templates
- **Functions:**
  - `template_exists()` - Checks if a template exists.
  - `_e()` - Escapes a string for safe output.
  - `__e()` - Translates and escapes a given text for safe HTML output.

**Many More....**

## Conclusion

HyperCore provides a set of core functionalities, utility methods, and helper classes to make web development with the Hyper MVT Framework efficient and enjoyable. Whether you are handling HTTP requests, managing databases, or working with templates, HyperCore has you covered.
