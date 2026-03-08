<?php

namespace App\Model;

class Paragraph extends Model
{
    protected static string $table = 'paragraphs';

    public static function findByNumber(int $bookId, int $number): ?array
    {
        return static::queryOne(
            'SELECT * FROM paragraphs WHERE book_id = :bid AND number = :num',
            [':bid' => $bookId, ':num' => $number]
        );
    }

    public static function getChoices(int $paragraphId): array
    {
        return static::query(
            'SELECT * FROM choices WHERE paragraph_id = :pid ORDER BY display_order, id',
            [':pid' => $paragraphId]
        );
    }

    public static function getCombatEncounters(int $paragraphId): array
    {
        return static::query(
            'SELECT * FROM combat_encounters WHERE paragraph_id = :pid ORDER BY display_order, id',
            [':pid' => $paragraphId]
        );
    }

    /**
     * Check whether a choice's condition is met for a given character.
     */
    public static function isChoiceAvailable(array $choice, int $characterId): bool
    {
        if (empty($choice['condition_type'])) {
            return true;
        }

        $db = static::db();

        switch ($choice['condition_type']) {
            case 'has_item':
                $stmt = $db->prepare(
                    'SELECT COUNT(*) FROM character_inventory
                     WHERE character_id = :cid AND LOWER(item_name) = LOWER(:item)'
                );
                $stmt->execute([':cid' => $characterId, ':item' => $choice['condition_param']]);
                return (int)$stmt->fetchColumn() > 0;

            case 'stat_gte':
                [$stat, $value] = explode(':', $choice['condition_param'], 2);
                $stmt = $db->prepare(
                    'SELECT current_value FROM character_stats
                     WHERE character_id = :cid AND stat_name = :stat'
                );
                $stmt->execute([':cid' => $characterId, ':stat' => $stat]);
                $row = $stmt->fetch();
                return $row && (int)$row['current_value'] >= (int)$value;

            case 'stat_lte':
                [$stat, $value] = explode(':', $choice['condition_param'], 2);
                $stmt = $db->prepare(
                    'SELECT current_value FROM character_stats
                     WHERE character_id = :cid AND stat_name = :stat'
                );
                $stmt->execute([':cid' => $characterId, ':stat' => $stat]);
                $row = $stmt->fetch();
                return $row && (int)$row['current_value'] <= (int)$value;
        }

        return true;
    }
}
