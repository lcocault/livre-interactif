<?php

namespace App\Controller;

use App\Model\GameSystem;
use App\Model\Character;
use App\Model\ReadingSession;
use App\Service\DiceService;

class CharacterController extends Controller
{
    private DiceService $dice;

    public function __construct()
    {
        $this->dice = new DiceService();
    }

    /** List current user's characters. */
    public function index(): void
    {
        $this->requireAuth();
        $characters = Character::findByUser($this->userId());
        $flash      = $this->getFlash();
        $this->render('character/index', compact('characters', 'flash'));
    }

    /** Character creation form. */
    public function createForm(): void
    {
        $this->requireAuth();
        $systems = GameSystem::all();
        $flash   = $this->getFlash();
        $csrf    = $this->csrfToken();
        $this->render('character/create', compact('systems', 'flash', 'csrf'));
    }

    /**
     * Roll stats for a given game system (AJAX or form submit).
     * Returns JSON with rolled values so the form can display them before confirming.
     */
    public function rollStats(): void
    {
        $this->requireAuth();

        $systemId = (int)($_GET['system_id'] ?? 0);
        $system   = GameSystem::find($systemId);
        if (!$system) {
            http_response_code(404);
            echo json_encode(['error' => 'Système de jeu introuvable.']);
            exit;
        }
        $system = GameSystem::decodeConfig($system);
        $cfg    = $system['config'];

        $rolls = [];
        foreach ($cfg['stats'] as $stat) {
            $result          = $this->dice->roll($stat['dice_count'], $stat['dice_sides'], $stat['bonus']);
            $rolls[$stat['name']] = [
                'label'   => $stat['label'],
                'formula' => $stat['roll_formula'],
                'rolls'   => $result['rolls'],
                'bonus'   => $result['bonus'],
                'total'   => $result['total'],
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($rolls);
        exit;
    }

    /** Store a new character with rolled stats. */
    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $systemId = (int)$this->input('game_system_id');
        $name     = $this->input('name');

        if (!$systemId || strlen($name) < 1) {
            $this->flash('danger', 'Nom de personnage et système de jeu requis.');
            $this->redirect('/characters/create');
        }

        $system = GameSystem::find($systemId);
        if (!$system) {
            $this->flash('danger', 'Système de jeu introuvable.');
            $this->redirect('/characters/create');
        }
        $system = GameSystem::decodeConfig($system);
        $cfg    = $system['config'];

        // Create character
        $characterId = Character::create($this->userId(), $systemId, $name);

        // Store stats (values were submitted by the form after rolling)
        foreach ($cfg['stats'] as $stat) {
            $statKey  = 'stat_' . $stat['name'];
            $value    = (int)($_POST[$statKey] ?? 0);
            // Clamp within valid range
            $value = max($stat['min_value'] ?? 1, min($stat['max_value'] ?? 999, $value));
            Character::addStat($characterId, $stat['name'], $value);
        }

        // Add initial equipment
        foreach ($cfg['initial_equipment'] ?? [] as $item) {
            Character::addInventoryItem($characterId, $item);
        }

        $this->flash('success', 'Personnage "' . htmlspecialchars($name) . '" créé avec succès !');
        $this->redirect('/characters/' . $characterId);
    }

    /** Show a character sheet. */
    public function show(array $params): void
    {
        $this->requireAuth();
        $characterId = (int)$params['id'];

        if (!Character::belongsTo($characterId, $this->userId())) {
            $this->notFound('Personnage introuvable.');
        }

        $character = Character::findWithSystem($characterId);
        if (!$character) {
            $this->notFound('Personnage introuvable.');
        }

        $stats     = Character::getStats($characterId);
        $inventory = Character::getInventory($characterId);
        $sessions  = ReadingSession::findByCharacter($characterId);

        // Decode game system config
        $gameSystemConfig = json_decode($character['game_system_config'], true);

        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();

        $this->render('character/show', compact(
            'character', 'stats', 'inventory', 'sessions', 'gameSystemConfig', 'flash', 'csrf'
        ));
    }

    /** Add an item to a character's inventory. */
    public function addItem(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $characterId = (int)$params['id'];
        if (!Character::belongsTo($characterId, $this->userId())) {
            $this->notFound('Personnage introuvable.');
        }

        $itemName = $this->input('item_name');
        $notes    = $this->input('notes');
        if ($itemName) {
            Character::addInventoryItem($characterId, $itemName, 1, $notes ?: null);
            $this->flash('success', 'Objet ajouté à l\'inventaire.');
        }

        $this->redirect('/characters/' . $characterId);
    }

    /** Remove an item from a character's inventory. */
    public function removeItem(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $characterId = (int)$params['id'];
        $itemId      = (int)$params['item_id'];

        if (!Character::belongsTo($characterId, $this->userId())) {
            $this->notFound('Personnage introuvable.');
        }

        Character::removeInventoryItem($itemId, $characterId);
        $this->flash('success', 'Objet retiré de l\'inventaire.');
        $this->redirect('/characters/' . $characterId);
    }
}
