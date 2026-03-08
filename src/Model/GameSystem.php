<?php

namespace App\Model;

class GameSystem extends Model
{
    protected static string $table = 'game_systems';

    public static function findBySlug(string $slug): ?array
    {
        return static::queryOne('SELECT * FROM game_systems WHERE slug = :slug', [':slug' => $slug]);
    }

    public static function all(string $orderBy = 'name'): array
    {
        return static::query('SELECT * FROM game_systems ORDER BY name');
    }

    /** Decode the jsonb config column into a PHP array. */
    public static function decodeConfig(array $system): array
    {
        if (is_string($system['config'])) {
            $system['config'] = json_decode($system['config'], true) ?? [];
        }
        return $system;
    }
}
