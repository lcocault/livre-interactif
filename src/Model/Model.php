<?php

namespace App\Model;

use App\Database;
use PDO;

/**
 * Base model providing common database helpers.
 */
abstract class Model
{
    protected static string $table = '';

    protected static function db(): PDO
    {
        return Database::getConnection();
    }

    protected static function query(string $sql, array $params = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    protected static function execute(string $sql, array $params = []): int
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Execute an INSERT … RETURNING id statement and return the new row's id.
     * The SQL must include "RETURNING id" at the end.
     */
    protected static function insertReturning(string $sql, array $params = []): int
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : 0;
    }

    /** Find a single row by id. */
    public static function find(int $id): ?array
    {
        return static::queryOne('SELECT * FROM ' . static::$table . ' WHERE id = :id', [':id' => $id]);
    }

    /** Return all rows. */
    public static function all(string $orderBy = 'id'): array
    {
        return static::query('SELECT * FROM ' . static::$table . ' ORDER BY ' . $orderBy);
    }
}
