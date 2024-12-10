<?php

namespace Hyper\Utils;

use Exception;

/**
 * Class Hash
 *
 * Provides methods for creating and validating hashed values, 
 * and encrypting/decrypting strings using secure algorithms.
 *
 * @package Hyper\Utils
 */
class Hash
{
    /**
     * Initializes the hash class with a key.
     * 
     * The key will be used for cryptographic operations.
     * 
     * If the key is not provided, the value of the app_key environment variable
     * will be used if it is set. Otherwise, a default key will be used.
     * 
     * The provided key must be at least 32 characters long. If the key is less than 32 characters,
     * an exception will be thrown.
     * 
     * @param string|null $key The encryption key to use.
     */
    public function __construct(private ?string $key = null)
    {
        // Get the key from the environment if not provided, otherwise use the default key
        if ($key === null) {
            $key = env('app_key', 'vHQBPufA2RSQYuk/ySGkBzQwrs6m1aM/NCdhx+DczhA=');
        }

        $this->setKey($key);
    }

    /**
     * Set the encryption key to use for cryptographic operations.
     *
     * The provided key must be at least 32 characters long. If the key is less than 32 characters,
     * an exception will be thrown.
     *
     * @param string $key The encryption key to use.
     * @throws Exception If the provided key is less than 32 characters.
     */
    public function setKey(string $key): void
    {
        // Check if the key is less than 32 characters
        if (strlen($key) < 32) {
            throw new Exception('The provided key must be at least 32 characters long.');
        }

        // Hash the key using SHA-256
        $this->key = hash('sha256', $key);
    }

    /**
     * Create a unique hash using the provided algorithm.
     *
     * @param string $plain The plaintext string to hash.
     * @param string $algo The hashing algorithm to use (default: sha256).
     * @return string The resulting hash.
     */
    public function make(string $plain, string $algo = 'sha256'): string
    {
        return hash_hmac($algo, $plain, $this->key);
    }

    /**
     * Validate a hash against a plain text value.
     *
     * @param string $plain The plaintext string.
     * @param string $hash The hash to validate.
     * @param string $algo The algorithm used to create the hash (default: sha256).
     * @return bool True if the hash matches, false otherwise.
     */
    public function validate(string $plain, string $hash, string $algo = 'sha256'): bool
    {
        return hash_equals($hash, $this->make($plain, $algo));
    }

    /**
     * Hash a password securely using Argon2id or Bcrypt.
     *
     * @param string $password The plain text password to hash.
     * @return string The hashed password.
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
    }

    /**
     * Validate a plain text password against a hashed password.
     *
     * @param string $password The plain text password to validate.
     * @param string $hashedPassword The hashed password to validate against.
     * @return bool True if the password matches, false otherwise.
     */
    public function validatePassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Encrypts a string using AES-256-CBC symmetric encryption.
     *
     * @param string $value The plaintext string to encrypt.
     * @return string The encrypted string, base64 encoded.
     * @throws Exception
     */
    public function encrypt(string $value): string
    {
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = random_bytes($ivLength);
        $cipherText = openssl_encrypt($value, 'AES-256-CBC', $this->key, 0, $iv);

        if ($cipherText === false) {
            throw new Exception('Encryption failed.');
        }

        return base64_encode("$cipherText::$iv");
    }

    /**
     * Decrypts a string encrypted with the encrypt method.
     *
     * @param string $encrypted The base64-encoded encrypted string.
     * @return string The decrypted plaintext string.
     * @throws Exception
     */
    public function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted);
        [$cipherText, $iv] = explode('::', $data, 2);

        if (!$cipherText || !$iv) {
            throw new Exception('Invalid encrypted data format.');
        }

        $plainText = openssl_decrypt($cipherText, 'AES-256-CBC', $this->key, 0, $iv);

        if ($plainText === false) {
            throw new Exception('Decryption failed.');
        }

        return $plainText;
    }
}
