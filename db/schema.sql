-- Livre Interactif - Database Schema
-- PostgreSQL

-- Game Systems (e.g., "Endurance/Habileté/Psi")
CREATE TABLE IF NOT EXISTS game_systems (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    config JSONB NOT NULL DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Books / Adventures
CREATE TABLE IF NOT EXISTS books (
    id SERIAL PRIMARY KEY,
    game_system_id INTEGER NOT NULL REFERENCES game_systems(id),
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    author VARCHAR(255),
    cover_image VARCHAR(255),
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Paragraphs (each numbered section of a book)
CREATE TABLE IF NOT EXISTS paragraphs (
    id SERIAL PRIMARY KEY,
    book_id INTEGER NOT NULL REFERENCES books(id) ON DELETE CASCADE,
    number INTEGER NOT NULL,
    content TEXT NOT NULL,
    is_final BOOLEAN NOT NULL DEFAULT FALSE,
    final_type VARCHAR(50), -- 'victory', 'death'
    UNIQUE(book_id, number)
);

-- Navigation Choices (linking paragraphs)
CREATE TABLE IF NOT EXISTS choices (
    id SERIAL PRIMARY KEY,
    paragraph_id INTEGER NOT NULL REFERENCES paragraphs(id) ON DELETE CASCADE,
    text TEXT NOT NULL,
    target_paragraph_number INTEGER NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    condition_type VARCHAR(50),  -- 'has_item', 'stat_gte', 'stat_lte', null = always available
    condition_param VARCHAR(100)
);

-- Combat Encounters (attached to a paragraph)
CREATE TABLE IF NOT EXISTS combat_encounters (
    id SERIAL PRIMARY KEY,
    paragraph_id INTEGER NOT NULL REFERENCES paragraphs(id) ON DELETE CASCADE,
    creature_name VARCHAR(255) NOT NULL,
    endurance INTEGER NOT NULL,
    -- combat_table: JSON array of {min_roll, max_roll, player_damage, creature_damage}
    combat_table JSONB NOT NULL,
    flee_allowed BOOLEAN NOT NULL DEFAULT FALSE,
    flee_paragraph_number INTEGER,
    on_victory_paragraph_number INTEGER,
    display_order INTEGER NOT NULL DEFAULT 0
);

-- Users (readers + admins)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Characters (one user may have several characters across game systems)
CREATE TABLE IF NOT EXISTS characters (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    game_system_id INTEGER NOT NULL REFERENCES game_systems(id),
    name VARCHAR(255) NOT NULL,
    is_alive BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Character Stats (ENDURANCE, HABILETÉ, PSI …)
CREATE TABLE IF NOT EXISTS character_stats (
    id SERIAL PRIMARY KEY,
    character_id INTEGER NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
    stat_name VARCHAR(50) NOT NULL,
    initial_value INTEGER NOT NULL,
    current_value INTEGER NOT NULL,
    UNIQUE(character_id, stat_name)
);

-- Character Inventory
CREATE TABLE IF NOT EXISTS character_inventory (
    id SERIAL PRIMARY KEY,
    character_id INTEGER NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
    item_name VARCHAR(255) NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Reading Sessions (tracks a character's progress through a book)
CREATE TABLE IF NOT EXISTS reading_sessions (
    id SERIAL PRIMARY KEY,
    character_id INTEGER NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
    book_id INTEGER NOT NULL REFERENCES books(id),
    current_paragraph_number INTEGER NOT NULL DEFAULT 1,
    started_at TIMESTAMP NOT NULL DEFAULT NOW(),
    last_activity_at TIMESTAMP NOT NULL DEFAULT NOW(),
    completed_at TIMESTAMP,
    outcome VARCHAR(50) -- 'victory', 'death', 'abandoned'
);

-- Paragraph History (which paragraphs has the character visited?)
CREATE TABLE IF NOT EXISTS paragraph_history (
    id SERIAL PRIMARY KEY,
    session_id INTEGER NOT NULL REFERENCES reading_sessions(id) ON DELETE CASCADE,
    paragraph_number INTEGER NOT NULL,
    visited_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Combat State (track ongoing fight within a session)
CREATE TABLE IF NOT EXISTS combat_states (
    id SERIAL PRIMARY KEY,
    session_id INTEGER NOT NULL REFERENCES reading_sessions(id) ON DELETE CASCADE,
    encounter_id INTEGER NOT NULL REFERENCES combat_encounters(id),
    creature_current_endurance INTEGER NOT NULL,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    outcome VARCHAR(20), -- 'victory', 'defeat', 'fled'
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_paragraphs_book ON paragraphs(book_id, number);
CREATE INDEX IF NOT EXISTS idx_choices_paragraph ON choices(paragraph_id);
CREATE INDEX IF NOT EXISTS idx_combat_encounters_paragraph ON combat_encounters(paragraph_id);
CREATE INDEX IF NOT EXISTS idx_characters_user ON characters(user_id);
CREATE INDEX IF NOT EXISTS idx_reading_sessions_character ON reading_sessions(character_id);
CREATE INDEX IF NOT EXISTS idx_paragraph_history_session ON paragraph_history(session_id);
CREATE INDEX IF NOT EXISTS idx_combat_states_session ON combat_states(session_id);
