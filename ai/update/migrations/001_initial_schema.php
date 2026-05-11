<?php
/**
 * Migration 001 — Initial schema baseline
 *
 * Creates the core tables that Yuga 1.0 needs.
 * Safe to run even if tables already exist (IF NOT EXISTS).
 */
function migrate_001_initial_schema(SQLite3 $db): void {

    // Subscribers (portal users / SaaS customers)
    $db->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        name        TEXT    NOT NULL,
        email       TEXT    NOT NULL UNIQUE,
        api_key     TEXT    NOT NULL UNIQUE,
        plan        TEXT    NOT NULL DEFAULT 'free',
        status      TEXT    NOT NULL DEFAULT 'active',
        created_at  INTEGER NOT NULL DEFAULT (strftime('%s','now')),
        expires_at  INTEGER
    )");

    // API usage tracking
    $db->exec("CREATE TABLE IF NOT EXISTS api_usage (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        api_key     TEXT    NOT NULL,
        endpoint    TEXT,
        used_at     INTEGER NOT NULL DEFAULT (strftime('%s','now'))
    )");

    // Payments
    $db->exec("CREATE TABLE IF NOT EXISTS payments (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        subscriber_id INTEGER,
        gateway     TEXT    NOT NULL,
        gateway_ref TEXT,
        amount      REAL,
        currency    TEXT    DEFAULT 'USD',
        status      TEXT    DEFAULT 'pending',
        created_at  INTEGER NOT NULL DEFAULT (strftime('%s','now'))
    )");

    // Indexes for hot paths
    $db->exec("CREATE INDEX IF NOT EXISTS idx_api_usage_key ON api_usage (api_key)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_api_usage_at  ON api_usage (used_at)");
}
