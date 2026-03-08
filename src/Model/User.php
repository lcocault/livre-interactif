<?php

namespace App\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByUsername(string $username): ?array
    {
        return static::queryOne(
            'SELECT * FROM users WHERE username = :u',
            [':u' => $username]
        );
    }

    public static function findByEmail(string $email): ?array
    {
        return static::queryOne(
            'SELECT * FROM users WHERE email = :e',
            [':e' => $email]
        );
    }

    public static function create(string $username, string $email, string $password, bool $isAdmin = false): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        return static::insertReturning(
            'INSERT INTO users (username, email, password_hash, is_admin)
             VALUES (:u, :e, :h, :a) RETURNING id',
            [':u' => $username, ':e' => $email, ':h' => $hash, ':a' => $isAdmin ? 't' : 'f']
        );
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    public static function existsAny(): bool
    {
        $stmt = static::db()->query('SELECT COUNT(*) FROM users');
        return (int)$stmt->fetchColumn() > 0;
    }
}
