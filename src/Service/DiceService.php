<?php

namespace App\Service;

/**
 * Dice rolling service.
 * Supports expressions like "2d6+20", "1d6+3".
 */
class DiceService
{
    /**
     * Roll dice and return [total, individual_rolls].
     *
     * @param  int   $count   Number of dice
     * @param  int   $sides   Sides per die
     * @param  int   $bonus   Fixed bonus added to the sum
     * @return array{total: int, rolls: int[], bonus: int}
     */
    public function roll(int $count, int $sides, int $bonus = 0): array
    {
        $rolls = [];
        for ($i = 0; $i < $count; $i++) {
            $rolls[] = random_int(1, $sides);
        }
        $total = array_sum($rolls) + $bonus;
        return ['total' => $total, 'rolls' => $rolls, 'bonus' => $bonus];
    }

    /**
     * Parse and evaluate a formula like "2d6+20" or "1d6+3".
     *
     * @return array{total: int, rolls: int[], bonus: int, formula: string}
     */
    public function rollFormula(string $formula): array
    {
        if (!preg_match('/^(\d+)d(\d+)(?:\+(\d+))?$/i', $formula, $m)) {
            throw new \InvalidArgumentException("Invalid dice formula: {$formula}");
        }
        $count = (int)$m[1];
        $sides = (int)$m[2];
        $bonus = isset($m[3]) ? (int)$m[3] : 0;
        $result = $this->roll($count, $sides, $bonus);
        $result['formula'] = $formula;
        return $result;
    }
}
