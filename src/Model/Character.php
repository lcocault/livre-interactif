<?php

namespace App\Model;

class Character extends Model
{
    protected static string $table = 'characters';

    public static function findByUser(int $userId): array
    {
        return static::query(
            'SELECT c.*, gs.name AS game_system_name, gs.slug AS game_system_slug
             FROM characters c
             JOIN game_systems gs ON gs.id = c.game_system_id
             WHERE c.user_id = :uid
             ORDER BY c.created_at DESC',
            [':uid' => $userId]
        );
    }

    public static function findWithSystem(int $characterId): ?array
    {
        return static::queryOne(
            'SELECT c.*, gs.name AS game_system_name, gs.slug AS game_system_slug,
                    gs.config AS game_system_config
             FROM characters c
             JOIN game_systems gs ON gs.id = c.game_system_id
             WHERE c.id = :id',
            [':id' => $characterId]
        );
    }

    public static function getStats(int $characterId): array
    {
        return static::query(
            'SELECT * FROM character_stats WHERE character_id = :cid ORDER BY id',
            [':cid' => $characterId]
        );
    }

    public static function getInventory(int $characterId): array
    {
        return static::query(
            'SELECT * FROM character_inventory WHERE character_id = :cid ORDER BY item_name',
            [':cid' => $characterId]
        );
    }

    public static function create(int $userId, int $gameSystemId, string $name): int
    {
        return static::insertReturning(
            'INSERT INTO characters (user_id, game_system_id, name)
             VALUES (:uid, :gsid, :name) RETURNING id',
            [':uid' => $userId, ':gsid' => $gameSystemId, ':name' => $name]
        );
    }

    public static function addStat(int $characterId, string $statName, int $value): void
    {
        static::db()->prepare(
            'INSERT INTO character_stats (character_id, stat_name, initial_value, current_value)
             VALUES (:cid, :stat, :init, :cur)
             ON CONFLICT (character_id, stat_name)
             DO UPDATE SET initial_value = :init, current_value = :cur'
        )->execute([':cid' => $characterId, ':stat' => $statName, ':init' => $value, ':cur' => $value]);
    }

    public static function addInventoryItem(int $characterId, string $itemName, int $quantity = 1, ?string $notes = null): void
    {
        static::db()->prepare(
            'INSERT INTO character_inventory (character_id, item_name, quantity, notes)
             VALUES (:cid, :item, :qty, :notes)'
        )->execute([':cid' => $characterId, ':item' => $itemName, ':qty' => $quantity, ':notes' => $notes]);
    }

    public static function removeInventoryItem(int $itemId, int $characterId): void
    {
        static::db()->prepare(
            'DELETE FROM character_inventory WHERE id = :id AND character_id = :cid'
        )->execute([':id' => $itemId, ':cid' => $characterId]);
    }

    public static function updateStat(int $characterId, string $statName, int $newValue): void
    {
        static::db()->prepare(
            'UPDATE character_stats SET current_value = :val
             WHERE character_id = :cid AND stat_name = :stat'
        )->execute([':val' => $newValue, ':cid' => $characterId, ':stat' => $statName]);
    }

    public static function getStatValue(int $characterId, string $statName): int
    {
        $row = static::queryOne(
            'SELECT current_value FROM character_stats WHERE character_id = :cid AND stat_name = :stat',
            [':cid' => $characterId, ':stat' => $statName]
        );
        return $row ? (int)$row['current_value'] : 0;
    }

    public static function belongsTo(int $characterId, int $userId): bool
    {
        $row = static::queryOne(
            'SELECT id FROM characters WHERE id = :id AND user_id = :uid',
            [':id' => $characterId, ':uid' => $userId]
        );
        return $row !== null;
    }
}
