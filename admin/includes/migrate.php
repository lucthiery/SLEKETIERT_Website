<?php
// Runs all DB migrations idempotently. Called at boot by CMS pages.
function run_migrations(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT NOT NULL,
            date        TEXT,
            location    TEXT,
            description TEXT,
            image       TEXT,
            status      TEXT DEFAULT 'upcoming',
            created_at  TEXT DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS photos (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT,
            filename    TEXT NOT NULL,
            album       TEXT,
            description TEXT,
            sort_order  INTEGER DEFAULT 0,
            created_at  TEXT DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS music (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT NOT NULL,
            artist      TEXT,
            date        TEXT,
            embed_url   TEXT,
            description TEXT,
            created_at  TEXT DEFAULT (datetime('now'))
        )
    ");
}
