<?php

namespace App\Model;

class Book extends Model
{
    protected static string $table = 'books';

    public static function findBySlug(string $slug): ?array
    {
        return static::queryOne(
            'SELECT b.*, gs.name AS game_system_name, gs.slug AS game_system_slug
             FROM books b
             JOIN game_systems gs ON gs.id = b.game_system_id
             WHERE b.slug = :slug',
            [':slug' => $slug]
        );
    }

    public static function allPublished(): array
    {
        return static::query(
            'SELECT b.*, gs.name AS game_system_name
             FROM books b
             JOIN game_systems gs ON gs.id = b.game_system_id
             WHERE b.is_published = TRUE
             ORDER BY b.title'
        );
    }

    public static function all(string $orderBy = 'title'): array
    {
        return static::query(
            'SELECT b.*, gs.name AS game_system_name
             FROM books b
             JOIN game_systems gs ON gs.id = b.game_system_id
             ORDER BY b.title'
        );
    }
}
