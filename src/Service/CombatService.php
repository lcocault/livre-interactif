<?php

namespace App\Service;

use App\Database;

/**
 * Handles the combat resolution logic for the EHP game system.
 */
class CombatService
{
    public function __construct(private readonly DiceService $dice) {}

    /**
     * Perform one round of combat.
     *
     * @param  array $encounter     Row from combat_encounters table (with decoded combat_table)
     * @param  int   $sessionId
     * @param  int   $combatStateId
     * @return array Result with roll, damages, new HP values, and whether combat ended.
     */
    public function doRound(array $encounter, int $sessionId, int $combatStateId): array
    {
        $db = Database::getConnection();

        // Retrieve current state
        $stmt = $db->prepare(
            'SELECT cs.creature_current_endurance, cs.is_completed, cs.outcome,
                    cs.encounter_id,
                    ch.id AS character_id
             FROM combat_states cs
             JOIN reading_sessions rs ON rs.id = cs.session_id
             JOIN characters ch ON ch.id = rs.character_id
             WHERE cs.id = :id AND cs.session_id = :sid'
        );
        $stmt->execute([':id' => $combatStateId, ':sid' => $sessionId]);
        $state = $stmt->fetch();

        if (!$state || $state['is_completed']) {
            return ['error' => 'Combat already completed or not found.'];
        }

        // Roll 2d6
        $roll = $this->dice->roll(2, 6);
        $rollTotal = $roll['total'];

        // Find matching row in the combat table
        $combatTable = json_decode($encounter['combat_table'], true);
        $damage = $this->lookupDamage($rollTotal, $combatTable);

        // Apply damage to player (ENDURANCE)
        $playerDamage   = $damage['player_damage'];
        $creatureDamage = $damage['creature_damage'];

        $creatureEndurance = $state['creature_current_endurance'] - $creatureDamage;
        $creatureEndurance = max(0, $creatureEndurance);

        $result = [
            'roll'             => $rollTotal,
            'dice'             => $roll['rolls'],
            'player_damage'    => $playerDamage,
            'creature_damage'  => $creatureDamage,
            'creature_endurance_before' => $state['creature_current_endurance'],
            'creature_endurance_after'  => $creatureEndurance,
            'combat_over'      => false,
            'outcome'          => null,
            'redirect_paragraph' => null,
        ];

        if ($playerDamage > 0) {
            $this->applyPlayerDamage($state['character_id'], 'ENDURANCE', $playerDamage, $db);
        }

        // Check player death
        $playerEndurance = $this->getStatValue($state['character_id'], 'ENDURANCE', $db);
        $result['player_endurance_after'] = $playerEndurance;

        if ($playerEndurance <= 0) {
            $this->finishCombat($combatStateId, $sessionId, 'defeat', $db);
            $this->killCharacter($state['character_id'], $db);
            $result['combat_over'] = true;
            $result['outcome']     = 'defeat';
            return $result;
        }

        // Check creature death
        $db->prepare(
            'UPDATE combat_states SET creature_current_endurance = :hp, updated_at = NOW() WHERE id = :id'
        )->execute([':hp' => $creatureEndurance, ':id' => $combatStateId]);

        if ($creatureEndurance <= 0) {
            $this->finishCombat($combatStateId, $sessionId, 'victory', $db);
            $result['combat_over']       = true;
            $result['outcome']           = 'victory';
            $result['redirect_paragraph'] = $encounter['on_victory_paragraph_number'] ?? null;
        }

        return $result;
    }

    /**
     * Attempt to flee combat.
     */
    public function flee(array $encounter, int $sessionId, int $combatStateId, array $gameSystemConfig): array
    {
        if (!$encounter['flee_allowed']) {
            return ['error' => 'La fuite n\'est pas possible dans ce combat.'];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT cs.*, ch.id AS character_id
             FROM combat_states cs
             JOIN reading_sessions rs ON rs.id = cs.session_id
             JOIN characters ch ON ch.id = rs.character_id
             WHERE cs.id = :id AND cs.session_id = :sid'
        );
        $stmt->execute([':id' => $combatStateId, ':sid' => $sessionId]);
        $state = $stmt->fetch();

        if (!$state) {
            return ['error' => 'État de combat introuvable.'];
        }

        $fleeCfg  = $gameSystemConfig['flee'];
        $roll     = $this->dice->roll($fleeCfg['dice_count'], $fleeCfg['dice_sides']);
        $habilete = $this->getStatValue($state['character_id'], $fleeCfg['compare_stat'], $db);

        $result = [
            'roll'         => $roll['total'],
            'dice'         => $roll['rolls'],
            'habilete'     => $habilete,
            'fled'         => false,
            'damage'       => 0,
            'redirect_paragraph' => null,
        ];

        // If roll > HABILETÉ: take damage and still flee
        if ($roll['total'] > $habilete) {
            $damage = $fleeCfg['fail_damage'];
            $this->applyPlayerDamage($state['character_id'], $fleeCfg['fail_stat'], $damage, $db);
            $result['damage'] = $damage;
            $playerEndurance = $this->getStatValue($state['character_id'], 'ENDURANCE', $db);
            $result['player_endurance_after'] = $playerEndurance;
            if ($playerEndurance <= 0) {
                $this->finishCombat($combatStateId, $sessionId, 'defeat', $db);
                $this->killCharacter($state['character_id'], $db);
                $result['outcome'] = 'defeat';
                return $result;
            }
        }

        $this->finishCombat($combatStateId, $sessionId, 'fled', $db);
        $result['fled']              = true;
        $result['outcome']           = 'fled';
        $result['redirect_paragraph'] = $encounter['flee_paragraph_number'] ?? null;
        return $result;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function lookupDamage(int $roll, array $combatTable): array
    {
        foreach ($combatTable as $row) {
            if ($roll >= $row['min_roll'] && $roll <= $row['max_roll']) {
                return $row;
            }
        }
        // Fallback
        return ['player_damage' => 0, 'creature_damage' => 0];
    }

    private function applyPlayerDamage(int $characterId, string $stat, int $damage, \PDO $db): void
    {
        $db->prepare(
            'UPDATE character_stats
             SET current_value = GREATEST(0, current_value - :dmg)
             WHERE character_id = :cid AND stat_name = :stat'
        )->execute([':dmg' => $damage, ':cid' => $characterId, ':stat' => $stat]);
    }

    private function getStatValue(int $characterId, string $stat, \PDO $db): int
    {
        $stmt = $db->prepare(
            'SELECT current_value FROM character_stats WHERE character_id = :cid AND stat_name = :stat'
        );
        $stmt->execute([':cid' => $characterId, ':stat' => $stat]);
        $row = $stmt->fetch();
        return $row ? (int)$row['current_value'] : 0;
    }

    private function finishCombat(int $combatStateId, int $sessionId, string $outcome, \PDO $db): void
    {
        $db->prepare(
            'UPDATE combat_states SET is_completed = TRUE, outcome = :outcome, updated_at = NOW()
             WHERE id = :id'
        )->execute([':outcome' => $outcome, ':id' => $combatStateId]);
    }

    private function killCharacter(int $characterId, \PDO $db): void
    {
        $db->prepare('UPDATE characters SET is_alive = FALSE WHERE id = :id')
           ->execute([':id' => $characterId]);
    }
}
