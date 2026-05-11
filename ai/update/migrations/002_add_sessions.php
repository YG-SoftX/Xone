<?php
/**
 * Migration 002 — Conversation sessions table
 *
 * Memory.php uses flat files by default, but if you want
 * DB-backed sessions this table is ready.  Also adds a
 * `monthly_limit` column to subscribers for plan enforcement.
 */
function migrate_002_add_sessions(SQLite3 $db): void {

    // Conversation sessions
    $db->exec("CREATE TABLE IF NOT EXISTS sessions (
        session_id  TEXT    PRIMARY KEY,
        api_key     TEXT    NOT NULL,
        created_at  INTEGER NOT NULL DEFAULT (strftime('%s','now')),
        updated_at  INTEGER NOT NULL DEFAULT (strftime('%s','now'))
    )");

    // Session messages (optional — Memory.php uses JSON files by default)
    $db->exec("CREATE TABLE IF NOT EXISTS session_messages (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id  TEXT    NOT NULL,
        role        TEXT    NOT NULL,
        content     TEXT    NOT NULL,
        created_at  INTEGER NOT NULL DEFAULT (strftime('%s','now'))
    )");

    // Add monthly_limit column to subscribers if not already there
    // SQLite doesn't support IF NOT EXISTS on ADD COLUMN, so we wrap in try/catch
    try {
        $db->exec("ALTER TABLE subscribers ADD COLUMN monthly_limit INTEGER DEFAULT 500");
    } catch (Exception $e) {
        // Column already exists — fine
    }

    $db->exec("CREATE INDEX IF NOT EXISTS idx_session_msg ON session_messages (session_id, created_at)");
}
