-- Example book import script
-- "Le Tombeau du Vampire" - sample paragraphs for demonstration
-- Import this via the admin panel SQL import feature

-- First, ensure the book exists
INSERT INTO books (game_system_id, title, slug, description, author, is_published)
SELECT gs.id, 'Le Tombeau du Vampire', 'tombeau-vampire',
       'Une aventure dans un château hanté où vous devrez affronter le seigneur des ténèbres.',
       'Exemple', TRUE
FROM game_systems gs WHERE gs.slug = 'ehp'
ON CONFLICT (slug) DO NOTHING;

-- Paragraph 1 - Introduction
INSERT INTO paragraphs (book_id, number, content) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    1,
    'Vous vous trouvez à l''orée d''une forêt sombre. Devant vous, un chemin mène vers un sinistre château. '
    'La nuit tombe et vous devez trouver un abri. Vous pouvez tenter d''entrer dans le château ou chercher '
    'une autre route.'
) ON CONFLICT (book_id, number) DO NOTHING;

-- Choices for paragraph 1
INSERT INTO choices (paragraph_id, text, target_paragraph_number, display_order)
SELECT p.id, 'Entrer dans le château', 2, 1 FROM paragraphs p
JOIN books b ON b.id = p.book_id WHERE b.slug = 'tombeau-vampire' AND p.number = 1
ON CONFLICT DO NOTHING;

INSERT INTO choices (paragraph_id, text, target_paragraph_number, display_order)
SELECT p.id, 'Chercher un autre chemin dans la forêt', 3, 2 FROM paragraphs p
JOIN books b ON b.id = p.book_id WHERE b.slug = 'tombeau-vampire' AND p.number = 1
ON CONFLICT DO NOTHING;

-- Paragraph 2 - Enter the castle
INSERT INTO paragraphs (book_id, number, content) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    2,
    'Vous poussez la lourde porte du château. Elle grince sinistrement. Dans l''entrée, vous apercevez '
    'un squelette animé qui garde le couloir. Il tourne la tête vers vous et dégaine son épée rouillée.'
) ON CONFLICT (book_id, number) DO NOTHING;

-- Combat encounter for paragraph 2
INSERT INTO combat_encounters (paragraph_id, creature_name, endurance, combat_table, flee_allowed, flee_paragraph_number, on_victory_paragraph_number)
SELECT p.id, 'Squelette Gardien', 8,
    '[
        {"min_roll": 2,  "max_roll": 4,  "player_damage": 3, "creature_damage": 0},
        {"min_roll": 5,  "max_roll": 7,  "player_damage": 2, "creature_damage": 1},
        {"min_roll": 8,  "max_roll": 10, "player_damage": 1, "creature_damage": 2},
        {"min_roll": 11, "max_roll": 12, "player_damage": 0, "creature_damage": 3}
    ]'::jsonb,
    TRUE, 1, 4
FROM paragraphs p
JOIN books b ON b.id = p.book_id WHERE b.slug = 'tombeau-vampire' AND p.number = 2
ON CONFLICT DO NOTHING;

-- Paragraph 3 - Forest path
INSERT INTO paragraphs (book_id, number, content) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    3,
    'Vous vous enfoncez dans la forêt. Le chemin serpente entre des arbres tordus. Après une longue '
    'marche, vous débouchez sur une clairière. Une vieille cabane y est abandonnée. '
    'Vous décidez d''y passer la nuit.'
) ON CONFLICT (book_id, number) DO NOTHING;

INSERT INTO choices (paragraph_id, text, target_paragraph_number, display_order)
SELECT p.id, 'Entrer dans la cabane', 5, 1 FROM paragraphs p
JOIN books b ON b.id = p.book_id WHERE b.slug = 'tombeau-vampire' AND p.number = 3
ON CONFLICT DO NOTHING;

-- Paragraph 4 - After defeating skeleton
INSERT INTO paragraphs (book_id, number, content) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    4,
    'Vous avez vaincu le squelette. Ses ossements s''éparpillent sur le sol de pierre. Un couloir s''ouvre devant vous. '
    'Vous pouvez entendre de l''eau couler quelque part plus loin.'
) ON CONFLICT (book_id, number) DO NOTHING;

INSERT INTO choices (paragraph_id, text, target_paragraph_number, display_order)
SELECT p.id, 'Avancer dans le couloir', 6, 1 FROM paragraphs p
JOIN books b ON b.id = p.book_id WHERE b.slug = 'tombeau-vampire' AND p.number = 4
ON CONFLICT DO NOTHING;

-- Paragraph 5 - Victory ending (simplified)
INSERT INTO paragraphs (book_id, number, content, is_final, final_type) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    5,
    'Vous vous reposez dans la cabane. Au petit matin, vous repartez vers d''autres aventures. '
    'Vous avez évité le château maudit, mais le vampire y règne toujours...',
    TRUE, 'victory'
) ON CONFLICT (book_id, number) DO NOTHING;

-- Paragraph 6 - Continue the adventure
INSERT INTO paragraphs (book_id, number, content) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    6,
    'Le couloir débouche sur une grande salle. Au centre, un sarcophage en pierre trône sur une estrade. '
    'Une inscription gravée avertit : "Ici repose le Seigneur des Ténèbres. Que celui qui le réveille '
    'affronte son courroux." Approchez-vous, ou faites demi-tour?'
) ON CONFLICT (book_id, number) DO NOTHING;

INSERT INTO choices (paragraph_id, text, target_paragraph_number, display_order)
SELECT p.id, 'Ouvrir le sarcophage', 7, 1 FROM paragraphs p
JOIN books b ON b.id = p.book_id WHERE b.slug = 'tombeau-vampire' AND p.number = 6
ON CONFLICT DO NOTHING;

INSERT INTO choices (paragraph_id, text, target_paragraph_number, display_order)
SELECT p.id, 'Faire demi-tour et fuir le château', 8, 2 FROM paragraphs p
JOIN books b ON b.id = p.book_id WHERE b.slug = 'tombeau-vampire' AND p.number = 6
ON CONFLICT DO NOTHING;

-- Paragraph 7 - Vampire final boss
INSERT INTO paragraphs (book_id, number, content) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    7,
    'Le couvercle du sarcophage se soulève dans un nuage de poussière. Le Vampire se lève, ses yeux '
    'brillant d''une lueur rouge sang. "Tu oses pénétrer dans ma demeure ? Tu vas le payer de ta vie !" '
    'Le combat est inévitable!'
) ON CONFLICT (book_id, number) DO NOTHING;

INSERT INTO combat_encounters (paragraph_id, creature_name, endurance, combat_table, flee_allowed, on_victory_paragraph_number)
SELECT p.id, 'Le Vampire Seigneur des Ténèbres', 20,
    '[
        {"min_roll": 2,  "max_roll": 3,  "player_damage": 5, "creature_damage": 0},
        {"min_roll": 4,  "max_roll": 5,  "player_damage": 4, "creature_damage": 0},
        {"min_roll": 6,  "max_roll": 7,  "player_damage": 3, "creature_damage": 1},
        {"min_roll": 8,  "max_roll": 9,  "player_damage": 2, "creature_damage": 2},
        {"min_roll": 10, "max_roll": 11, "player_damage": 1, "creature_damage": 3},
        {"min_roll": 12, "max_roll": 12, "player_damage": 0, "creature_damage": 5}
    ]'::jsonb,
    FALSE, 9
FROM paragraphs p
JOIN books b ON b.id = p.book_id WHERE b.slug = 'tombeau-vampire' AND p.number = 7
ON CONFLICT DO NOTHING;

-- Paragraph 8 - Escape
INSERT INTO paragraphs (book_id, number, content, is_final, final_type) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    8,
    'Vous fuyez le château en courant. Derrière vous, un rire sinistre résonne dans la nuit. '
    'Vous avez survécu, mais le vampire règne toujours sur ces terres maudites. '
    'Peut-être reviendrez-vous mieux préparé?',
    TRUE, 'victory'
) ON CONFLICT (book_id, number) DO NOTHING;

-- Paragraph 9 - Final victory
INSERT INTO paragraphs (book_id, number, content, is_final, final_type) VALUES (
    (SELECT id FROM books WHERE slug = 'tombeau-vampire'),
    9,
    'Le vampire s''effondre en poussière! Ses cendres se dispersent dans l''air. '
    'Un cri de triomphe vous échappe. Vous avez vaincu le Seigneur des Ténèbres! '
    'Le château commence à s''effondrer autour de vous. Vous fuyez en courant et sortez à l''air libre '
    'juste à temps. L''aube se lève, teintant le ciel de rose et d''or. '
    'FÉLICITATIONS ! Vous avez accompli votre quête avec succès!',
    TRUE, 'victory'
) ON CONFLICT (book_id, number) DO NOTHING;
