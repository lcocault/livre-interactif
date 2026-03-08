<?php

namespace App\Model;

class ReadingSession extends Model
{
    protected static string $table = 'reading_sessions';

    public static function findActiveForCharacterAndBook(int $characterId, int $bookId): ?array
    {
        return static::queryOne(
            'SELECT * FROM reading_sessions
             WHERE character_id = :cid AND book_id = :bid AND completed_at IS NULL
             ORDER BY started_at DESC
             LIMIT 1',
            [':cid' => $characterId, ':bid' => $bookId]
        );
    }

    public static function create(int $characterId, int $bookId, int $startParagraph = 1): int
    {
        return static::insertReturning(
            'INSERT INTO reading_sessions (character_id, book_id, current_paragraph_number)
             VALUES (:cid, :bid, :para) RETURNING id',
            [':cid' => $characterId, ':bid' => $bookId, ':para' => $startParagraph]
        );
    }

    public static function updateParagraph(int $sessionId, int $paragraphNumber): void
    {
        static::db()->prepare(
            'UPDATE reading_sessions
             SET current_paragraph_number = :para, last_activity_at = NOW()
             WHERE id = :id'
        )->execute([':para' => $paragraphNumber, ':id' => $sessionId]);
    }

    public static function complete(int $sessionId, string $outcome): void
    {
        static::db()->prepare(
            'UPDATE reading_sessions
             SET completed_at = NOW(), outcome = :outcome, last_activity_at = NOW()
             WHERE id = :id'
        )->execute([':outcome' => $outcome, ':id' => $sessionId]);
    }

    public static function recordParagraphVisit(int $sessionId, int $paragraphNumber): void
    {
        static::db()->prepare(
            'INSERT INTO paragraph_history (session_id, paragraph_number)
             VALUES (:sid, :para)'
        )->execute([':sid' => $sessionId, ':para' => $paragraphNumber]);
    }

    public static function getHistory(int $sessionId): array
    {
        return static::query(
            'SELECT * FROM paragraph_history WHERE session_id = :sid ORDER BY visited_at',
            [':sid' => $sessionId]
        );
    }

    public static function getActiveCombat(int $sessionId): ?array
    {
        return static::queryOne(
            'SELECT cs.*, ce.creature_name, ce.endurance AS creature_max_endurance,
                    ce.combat_table, ce.flee_allowed, ce.flee_paragraph_number,
                    ce.on_victory_paragraph_number
             FROM combat_states cs
             JOIN combat_encounters ce ON ce.id = cs.encounter_id
             WHERE cs.session_id = :sid AND cs.is_completed = FALSE
             ORDER BY cs.id DESC
             LIMIT 1',
            [':sid' => $sessionId]
        );
    }

    public static function startCombat(int $sessionId, int $encounterId, int $creatureEndurance): int
    {
        return static::insertReturning(
            'INSERT INTO combat_states (session_id, encounter_id, creature_current_endurance)
             VALUES (:sid, :eid, :hp) RETURNING id',
            [':sid' => $sessionId, ':eid' => $encounterId, ':hp' => $creatureEndurance]
        );
    }

    public static function findByCharacter(int $characterId): array
    {
        return static::query(
            'SELECT rs.*, b.title AS book_title, b.slug AS book_slug
             FROM reading_sessions rs
             JOIN books b ON b.id = rs.book_id
             WHERE rs.character_id = :cid
             ORDER BY rs.last_activity_at DESC',
            [':cid' => $characterId]
        );
    }

    public static function belongsTo(int $sessionId, int $userId): bool
    {
        $row = static::queryOne(
            'SELECT rs.id FROM reading_sessions rs
             JOIN characters c ON c.id = rs.character_id
             WHERE rs.id = :sid AND c.user_id = :uid',
            [':sid' => $sessionId, ':uid' => $userId]
        );
        return $row !== null;
    }
}
