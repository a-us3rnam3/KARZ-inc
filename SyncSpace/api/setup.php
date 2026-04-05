<?php
// SyncSpace — Database Setup
// Run once to create all tables: http://localhost/SyncSpace/api/setup.php

require 'db.php';

$tables = [];

// ─── USERS ────────────────────────────────────────────────────────────────────
$tables['users'] = "
    CREATE TABLE IF NOT EXISTS users (
        user_id       INT           AUTO_INCREMENT PRIMARY KEY,
        username      VARCHAR(50)   NOT NULL UNIQUE,
        email         VARCHAR(100)  NOT NULL UNIQUE,
        password_hash VARCHAR(255)  NOT NULL,
        created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

// ─── USER_GROUPS ──────────────────────────────────────────────────────────────
$tables['user_groups'] = "
    CREATE TABLE IF NOT EXISTS user_groups (
        group_id    INT           AUTO_INCREMENT PRIMARY KEY,
        group_name  VARCHAR(100)  NOT NULL,
        description TEXT,
        created_by  INT           NOT NULL,
        created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

// ─── GROUP_MEMBERS ────────────────────────────────────────────────────────────
$tables['group_members'] = "
    CREATE TABLE IF NOT EXISTS group_members (
        membership_id INT          AUTO_INCREMENT PRIMARY KEY,
        group_id      INT          NOT NULL,
        user_id       INT          NOT NULL,
        role          VARCHAR(20)  NOT NULL DEFAULT 'member',
        joined_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES user_groups(group_id),
        FOREIGN KEY (user_id)  REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

// ─── EVENTS ───────────────────────────────────────────────────────────────────
$tables['events'] = "
    CREATE TABLE IF NOT EXISTS events (
        event_id        INT           AUTO_INCREMENT PRIMARY KEY,
        title           VARCHAR(100)  NOT NULL,
        description     TEXT,
        start_time      DATETIME      NOT NULL,
        end_time        DATETIME      NOT NULL,
        created_by      INT           NOT NULL,
        owner_user_id   INT           DEFAULT NULL,
        owner_group_id  INT           DEFAULT NULL,
        location        VARCHAR(150),
        is_all_day      TINYINT(1)    NOT NULL DEFAULT 0,
        priority        VARCHAR(10)   NOT NULL DEFAULT 'medium',
        anonymous       TINYINT(1)    NOT NULL DEFAULT 0,
        created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by)      REFERENCES users(user_id),
        FOREIGN KEY (owner_user_id)   REFERENCES users(user_id),
        FOREIGN KEY (owner_group_id)  REFERENCES user_groups(group_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

// ─── RECURRING_EVENT ──────────────────────────────────────────────────────────
$tables['recurring_event'] = "
    CREATE TABLE IF NOT EXISTS recurring_event (
        recurring_id      INT          AUTO_INCREMENT PRIMARY KEY,
        event_id          INT          NOT NULL,
        repeat_type       VARCHAR(20)  NOT NULL,
        interval_value    INT          NOT NULL DEFAULT 1,
        days_of_week      VARCHAR(20)  DEFAULT NULL,
        end_date          DATETIME     DEFAULT NULL,
        occurrence_count  INT          DEFAULT NULL,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

// ─── EVENT_EXCEPTIONS ─────────────────────────────────────────────────────────
$tables['event_exceptions'] = "
    CREATE TABLE IF NOT EXISTS event_exceptions (
        exception_id    INT           AUTO_INCREMENT PRIMARY KEY,
        recurring_id    INT           NOT NULL,
        original_date   DATETIME      NOT NULL,
        is_cancelled    TINYINT(1)    NOT NULL DEFAULT 0,
        new_start_time  DATETIME      DEFAULT NULL,
        new_end_time    DATETIME      DEFAULT NULL,
        new_title       VARCHAR(100)  DEFAULT NULL,
        new_description TEXT          DEFAULT NULL,
        FOREIGN KEY (recurring_id) REFERENCES recurring_event(recurring_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

// UNIQUE KEY is very useful for preventing dupes
// Join table to allow for an event to linked to as many groups as needed
// ─── EVENT_GROUPS ─────────────────────────────────────────────────────────
$tables['event_groups'] = "
    CREATE TABLE IF NOT EXISTS event_groups (
        event_group_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        group_id INT NOT NULL,
        is_anonym_in_group TINYINT(1) NOT NULL DEFAULT 0,

        UNIQUE KEY unique_event_group (event_id, group_id),

        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (group_id) REFERENCES user_groups(group_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

// ─── Run ──────────────────────────────────────────────────────────────────────

$results = [];
foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = "✓ $name";
    } catch (PDOException $e) {
        $results[] = "✗ $name — " . $e->getMessage();
    }
}

// ─── Seed: placeholder user so events can be added before auth is integrated ──
try {
    $pdo->exec("
        INSERT IGNORE INTO users (user_id, username, email, password_hash)
        VALUES (1, 'placeholder', 'placeholder@syncspace.local', 'no-auth-yet')
    ");
    $results[] = "✓ placeholder user (id=1)";
} catch (PDOException $e) {
    $results[] = "✗ placeholder user — " . $e->getMessage();
}

echo "<pre>" . implode("\n", $results) . "\n\nDone.</pre>";
