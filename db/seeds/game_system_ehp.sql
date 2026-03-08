-- Seed data: Endurance / Habileté / Psi game system
-- Used in "Défis Fantastiques" / "Livres Dont Vous Êtes Le Héros" series

INSERT INTO game_systems (name, slug, description, config) VALUES (
    'Endurance / Habileté / Psi',
    'ehp',
    'Système de jeu classique des "Défis Fantastiques". '
    'Les trois attributs principaux sont l''Endurance, l''Habileté et le Psi.',
    '{
        "stats": [
            {
                "name": "ENDURANCE",
                "label": "Endurance",
                "description": "Force, volonté de survivre, détermination et forme physique et morale. Si ce total atteint 0, vous êtes mort.",
                "roll_formula": "2d6+20",
                "dice_count": 2,
                "dice_sides": 6,
                "bonus": 20,
                "min_value": 22,
                "max_value": 32
            },
            {
                "name": "HABILETE",
                "label": "Habileté",
                "description": "Agilité. Nécessaire pour escalader des murs, franchir des obstacles ou passer inaperçu.",
                "roll_formula": "1d6+3",
                "dice_count": 1,
                "dice_sides": 6,
                "bonus": 3,
                "min_value": 4,
                "max_value": 9
            },
            {
                "name": "PSI",
                "label": "Psi",
                "description": "Résistance aux sortilèges, réceptivité aux communications psychiques et force de volonté.",
                "roll_formula": "1d6+3",
                "dice_count": 1,
                "dice_sides": 6,
                "bonus": 3,
                "min_value": 4,
                "max_value": 9
            }
        ],
        "initial_equipment": [
            "Poignard",
            "Épée",
            "Armure",
            "Gourde d'\''eau fraîche",
            "Sac à dos"
        ],
        "combat": {
            "roll_dice": "2d6",
            "dice_count": 2,
            "dice_sides": 6,
            "damage_stat": "ENDURANCE",
            "default_combat_table": [
                {"min_roll": 2,  "max_roll": 3,  "player_damage": 4, "creature_damage": 0},
                {"min_roll": 4,  "max_roll": 5,  "player_damage": 3, "creature_damage": 0},
                {"min_roll": 6,  "max_roll": 7,  "player_damage": 2, "creature_damage": 1},
                {"min_roll": 8,  "max_roll": 9,  "player_damage": 1, "creature_damage": 2},
                {"min_roll": 10, "max_roll": 11, "player_damage": 0, "creature_damage": 3},
                {"min_roll": 12, "max_roll": 12, "player_damage": 0, "creature_damage": 4}
            ]
        },
        "flee": {
            "roll_dice": "2d6",
            "dice_count": 2,
            "dice_sides": 6,
            "compare_stat": "HABILETE",
            "compare_mode": "gt",
            "fail_damage": 3,
            "fail_stat": "ENDURANCE",
            "description": "Jetez deux dés. Si le résultat est supérieur à votre Habileté, vous recevez un coup dans le dos et perdez 3 points d'\''Endurance."
        },
        "death_condition": {
            "stat": "ENDURANCE",
            "threshold": 0
        }
    }'::jsonb
) ON CONFLICT (slug) DO NOTHING;
