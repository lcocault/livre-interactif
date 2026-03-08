<?php

namespace App\Controller;

use App\Model\ReadingSession;
use App\Model\GameSystem;
use App\Model\Character;
use App\Model\Paragraph;
use App\Service\DiceService;
use App\Service\CombatService;

class GameController extends Controller
{
    private CombatService $combat;

    public function __construct()
    {
        $this->combat = new CombatService(new DiceService());
    }

    /** Start a combat encounter (called when a paragraph has encounters and none is active). */
    public function startCombat(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $sessionId   = (int)$params['session'];
        $encounterId = (int)$this->input('encounter_id');

        if (!ReadingSession::belongsTo($sessionId, $this->userId())) {
            $this->notFound('Session introuvable.');
        }

        // Fetch the encounter
        $encounter = $this->fetchEncounter($encounterId);
        if (!$encounter) {
            $this->notFound('Rencontre introuvable.');
        }

        // Verify the encounter belongs to the current paragraph
        $session = ReadingSession::find($sessionId);
        $para    = Paragraph::findByNumber($session['book_id'], $session['current_paragraph_number']);
        if (!$para || $encounter['paragraph_id'] != $para['id']) {
            $this->flash('danger', 'Cette rencontre n\'est pas disponible ici.');
            $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
        }

        // Prevent starting another combat if one is already active
        if (ReadingSession::getActiveCombat($sessionId)) {
            $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
        }

        ReadingSession::startCombat($sessionId, $encounterId, (int)$encounter['endurance']);
        $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
    }

    /** Perform one combat round. */
    public function combatRound(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $sessionId     = (int)$params['session'];
        $combatStateId = (int)$this->input('combat_state_id');

        if (!ReadingSession::belongsTo($sessionId, $this->userId())) {
            $this->notFound('Session introuvable.');
        }

        $activeCombat = ReadingSession::getActiveCombat($sessionId);
        if (!$activeCombat || $activeCombat['id'] != $combatStateId) {
            $this->flash('danger', 'Aucun combat actif trouvé.');
            $session = ReadingSession::find($sessionId);
            $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
        }

        $result  = $this->combat->doRound($activeCombat, $sessionId, $combatStateId);
        $session = ReadingSession::find($sessionId);

        if (!empty($result['error'])) {
            $this->flash('danger', $result['error']);
            $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
        }

        // Store result in session for display
        $_SESSION['last_combat_result'] = $result;

        if ($result['combat_over']) {
            if ($result['outcome'] === 'defeat') {
                ReadingSession::complete($sessionId, 'death');
                $this->flash('danger', 'Votre personnage est mort au combat. L\'aventure est terminée.');
                $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
            } elseif ($result['outcome'] === 'victory' && $result['redirect_paragraph']) {
                $this->redirect('/read/' . $sessionId . '/' . $result['redirect_paragraph']);
            }
        }

        $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
    }

    /** Attempt to flee a combat. */
    public function flee(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $sessionId     = (int)$params['session'];
        $combatStateId = (int)$this->input('combat_state_id');

        if (!ReadingSession::belongsTo($sessionId, $this->userId())) {
            $this->notFound('Session introuvable.');
        }

        $activeCombat = ReadingSession::getActiveCombat($sessionId);
        if (!$activeCombat) {
            $session = ReadingSession::find($sessionId);
            $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
        }

        // Fetch game system config
        $charId = $this->getSessionCharacterId($sessionId);
        $char   = Character::findWithSystem($charId);
        $cfg    = json_decode($char['game_system_config'], true);

        $result  = $this->combat->flee($activeCombat, $sessionId, $combatStateId, $cfg);
        $session = ReadingSession::find($sessionId);

        if (!empty($result['error'])) {
            $this->flash('danger', $result['error']);
            $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
        }

        $_SESSION['last_combat_result'] = $result;

        if (isset($result['outcome'])) {
            if ($result['outcome'] === 'defeat') {
                ReadingSession::complete($sessionId, 'death');
                $this->flash('danger', 'Votre personnage est mort en tentant de fuir.');
                $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
            } elseif ($result['fled'] && $result['redirect_paragraph']) {
                $this->redirect('/read/' . $sessionId . '/' . $result['redirect_paragraph']);
            }
        }

        $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function fetchEncounter(int $encounterId): ?array
    {
        $stmt = \App\Database::getConnection()->prepare(
            'SELECT * FROM combat_encounters WHERE id = :id'
        );
        $stmt->execute([':id' => $encounterId]);
        return $stmt->fetch() ?: null;
    }

    private function getSessionCharacterId(int $sessionId): int
    {
        $session = ReadingSession::find($sessionId);
        return (int)($session['character_id'] ?? 0);
    }
}
