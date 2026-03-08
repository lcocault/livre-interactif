<?php

namespace App\Controller;

use App\Model\Book;
use App\Model\Paragraph;
use App\Model\Character;
use App\Model\ReadingSession;

class BookController extends Controller
{
    /** List all published books. */
    public function index(): void
    {
        $books = Book::allPublished();
        $flash = $this->getFlash();
        $this->render('book/index', compact('books', 'flash'));
    }

    /** Show book details page. */
    public function show(array $params): void
    {
        $book = Book::findBySlug($params['slug']);
        if (!$book) {
            $this->notFound('Livre introuvable.');
        }

        $characters = [];
        if ($this->userId()) {
            // Only characters matching the book's game system
            $all = Character::findByUser($this->userId());
            foreach ($all as $c) {
                if ($c['game_system_id'] === $book['game_system_id'] && $c['is_alive']) {
                    $characters[] = $c;
                }
            }
        }

        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();
        $this->render('book/show', compact('book', 'characters', 'flash', 'csrf'));
    }

    /** Start a new reading session or resume an existing one. */
    public function startReading(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $book = Book::findBySlug($params['slug']);
        if (!$book) {
            $this->notFound('Livre introuvable.');
        }

        $characterId = (int)$this->input('character_id');
        if (!$characterId || !Character::belongsTo($characterId, $this->userId())) {
            $this->flash('danger', 'Personnage invalide.');
            $this->redirect('/books/' . $params['slug']);
        }

        $char = Character::findWithSystem($characterId);
        if (!$char || !$char['is_alive']) {
            $this->flash('danger', 'Ce personnage est mort et ne peut plus lire de nouvelles aventures.');
            $this->redirect('/books/' . $params['slug']);
        }

        // Check game system compatibility
        if ($char['game_system_id'] != $book['game_system_id']) {
            $this->flash('danger', 'Ce personnage n\'est pas compatible avec le système de jeu de ce livre.');
            $this->redirect('/books/' . $params['slug']);
        }

        $action = $this->input('action', 'resume');

        if ($action === 'new') {
            // Always start fresh
            $sessionId = ReadingSession::create($characterId, $book['id']);
        } else {
            // Resume existing or start new
            $session = ReadingSession::findActiveForCharacterAndBook($characterId, $book['id']);
            $sessionId = $session ? $session['id'] : ReadingSession::create($characterId, $book['id']);
        }

        $this->redirect('/read/' . $sessionId . '/1');
    }

    /** Read a specific paragraph in a session. */
    public function readParagraph(array $params): void
    {
        $this->requireAuth();

        $sessionId       = (int)$params['session'];
        $paragraphNumber = (int)$params['paragraph'];

        if (!ReadingSession::belongsTo($sessionId, $this->userId())) {
            $this->notFound('Session introuvable.');
        }

        $session = ReadingSession::find($sessionId);
        if (!$session) {
            $this->notFound('Session introuvable.');
        }

        $book = Book::find($session['book_id']);
        if (!$book) {
            $this->notFound('Livre introuvable.');
        }

        $paragraph = Paragraph::findByNumber($book['id'], $paragraphNumber);
        if (!$paragraph) {
            $this->notFound("Paragraphe {$paragraphNumber} introuvable.");
        }

        // Update current paragraph in session
        ReadingSession::updateParagraph($sessionId, $paragraphNumber);

        // Record visit in history (avoid duplicate consecutive entries)
        $history = ReadingSession::getHistory($sessionId);
        $lastVisited = $history ? (int)end($history)['paragraph_number'] : 0;
        if ($lastVisited !== $paragraphNumber) {
            ReadingSession::recordParagraphVisit($sessionId, $paragraphNumber);
        }

        // If it's a final paragraph, complete the session
        if ($paragraph['is_final'] && !$session['completed_at']) {
            ReadingSession::complete($sessionId, $paragraph['final_type'] ?? 'victory');
        }

        $characterId = $session['character_id'];
        $character   = Character::findWithSystem($characterId);
        $stats       = Character::getStats($characterId);
        $inventory   = Character::getInventory($characterId);

        // Index stats by name for easy access
        $statsMap = [];
        foreach ($stats as $s) {
            $statsMap[$s['stat_name']] = $s;
        }

        // Get choices (filtered by conditions)
        $allChoices = Paragraph::getChoices($paragraph['id']);
        $choices    = [];
        foreach ($allChoices as $choice) {
            if (Paragraph::isChoiceAvailable($choice, $characterId)) {
                $choices[] = $choice;
            }
        }

        // Get combat encounters
        $encounters = Paragraph::getCombatEncounters($paragraph['id']);
        foreach ($encounters as &$enc) {
            $enc['combat_table'] = json_decode($enc['combat_table'], true);
        }
        unset($enc);

        // Check for an active combat in this session
        $activeCombat = ReadingSession::getActiveCombat($sessionId);
        if ($activeCombat) {
            $activeCombat['combat_table'] = json_decode($activeCombat['combat_table'], true);
        }

        $gameSystemConfig = json_decode($character['game_system_config'], true);

        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();

        $this->render('book/read', compact(
            'book', 'paragraph', 'choices', 'encounters',
            'session', 'character', 'stats', 'statsMap', 'inventory',
            'activeCombat', 'gameSystemConfig', 'flash', 'csrf'
        ));
    }

    /** Navigate to a chosen paragraph. */
    public function navigate(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $sessionId = (int)$params['session'];

        if (!ReadingSession::belongsTo($sessionId, $this->userId())) {
            $this->notFound('Session introuvable.');
        }

        $session = ReadingSession::find($sessionId);

        // Check there's no active combat blocking navigation
        $activeCombat = ReadingSession::getActiveCombat($sessionId);
        if ($activeCombat) {
            $this->flash('danger', 'Vous devez d\'abord résoudre le combat avant de continuer.');
            $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
        }

        $targetParagraph = (int)$this->input('target_paragraph');
        if (!$targetParagraph) {
            $this->redirect('/read/' . $sessionId . '/' . $session['current_paragraph_number']);
        }

        $this->redirect('/read/' . $sessionId . '/' . $targetParagraph);
    }
}
