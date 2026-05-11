<?php
/**
 * Migration 003 — Feedback / thumbs-up / thumbs-down table
 *
 * Allows portal users to rate answers.
 * Feedback.php reads from this table to improve retrieval weights.
 */
function migrate_003_add_feedback(SQLite3 $db): void {

    $db->exec("CREATE TABLE IF NOT EXISTS feedback (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        api_key     TEXT    NOT NULL,
        session_id  TEXT,
        question    TEXT,
        answer      TEXT,
        rating      INTEGER NOT NULL DEFAULT 0,  -- +1 or -1
        created_at  INTEGER NOT NULL DEFAULT (strftime('%s','now'))
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_feedback_key ON feedback (api_key, created_at)");
}
