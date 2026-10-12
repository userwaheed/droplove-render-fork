<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $opt = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
        if (str_starts_with(DB_DSN,'postgresql://')) {
            $url = preg_replace('/\?.*$/', '', DB_DSN);
            $pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
            preg_match($pattern, $url, $m);
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s;sslmode=prefer',
                $m[3], $m[4], $m[5], $m[1], $m[2]);
            $pdo = new PDO($dsn, null, null, $opt);
            $pdo->exec("SET client_encoding = 'UTF8'");
        } else {
            $dir=__DIR__ . '/data'; if(!is_dir($dir)) @mkdir($dir,0775,true);
            $pdo=new PDO('sqlite:'.$dir.'/vk_match.db',null,null,$opt+[PDO::ATTR_EMULATE_PREPARES=>false]);
            $pdo->exec('PRAGMA journal_mode=WAL'); $pdo->exec('PRAGMA foreign_keys=ON'); $pdo->exec('PRAGMA synchronous=NORMAL');
        }
        schema_init($pdo); schema_migrate($pdo);
    }
    return $pdo;
}
function is_postgres(): bool { return str_starts_with(DB_DSN,'postgresql://'); }
function schema_init(PDO $pdo): void { if(is_postgres()) schema_init_pg($pdo); else schema_init_sqlite($pdo); }

function schema_init_pg(PDO $pdo): void {
    $pdo->exec(
    "
    CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL,
        username TEXT NOT NULL UNIQUE, first_name TEXT NOT NULL, last_name TEXT,
        gender TEXT NOT NULL CHECK (gender IN ('male','female','other')),
        category TEXT NOT NULL DEFAULT 'single_m', orientation TEXT NOT NULL DEFAULT 'flexible',
        birth_date TEXT NOT NULL, city TEXT, country TEXT, zip TEXT,
        bio TEXT NOT NULL DEFAULT '', interests TEXT NOT NULL DEFAULT '', looking_for TEXT NOT NULL DEFAULT '',
        body_type TEXT, height_cm INTEGER, avatar TEXT NOT NULL DEFAULT '', cover TEXT NOT NULL DEFAULT '',
        is_active INTEGER NOT NULL DEFAULT 0, is_admin INTEGER NOT NULL DEFAULT 0,
        banned INTEGER NOT NULL DEFAULT 0, verified INTEGER NOT NULL DEFAULT 0,
        visibility TEXT NOT NULL DEFAULT 'members', online_last TIMESTAMPTZ,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), updated_at TIMESTAMPTZ);
    CREATE TABLE IF NOT EXISTS profile_photos (
        id SERIAL PRIMARY KEY, user_id INTEGER NOT NULL, photo TEXT NOT NULL,
        is_cover INTEGER NOT NULL DEFAULT 0, position INTEGER NOT NULL DEFAULT 0,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);
    CREATE TABLE IF NOT EXISTS login_attempts (
        id SERIAL PRIMARY KEY, ip TEXT NOT NULL, identifier TEXT NOT NULL,
        success INTEGER NOT NULL DEFAULT 0, created_at TIMESTAMPTZ NOT NULL DEFAULT NOW());
    CREATE TABLE IF NOT EXISTS likes (
        user_id INTEGER NOT NULL, target_id INTEGER NOT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), PRIMARY KEY (user_id,target_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE);
    CREATE TABLE IF NOT EXISTS messages (
        id SERIAL PRIMARY KEY, from_id INTEGER NOT NULL, to_id INTEGER NOT NULL,
        body TEXT NOT NULL, is_read INTEGER NOT NULL DEFAULT 0,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE);
    CREATE TABLE IF NOT EXISTS reports (
        id SERIAL PRIMARY KEY, reporter INTEGER, target_id INTEGER NOT NULL,
        reason TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'open',
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        FOREIGN KEY (reporter) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE);
    CREATE TABLE IF NOT EXISTS uploads (
        id SERIAL PRIMARY KEY, filename TEXT NOT NULL UNIQUE, mime TEXT NOT NULL,
        data BYTEA NOT NULL, size INTEGER NOT NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT NOW());
    CREATE INDEX IF NOT EXISTS idx_users_category ON users(category);
    CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active,banned);
    CREATE INDEX IF NOT EXISTS idx_users_gender ON users(gender);
    CREATE INDEX IF NOT EXISTS idx_photos_user ON profile_photos(user_id);
    CREATE INDEX IF NOT EXISTS idx_messages_pair ON messages(from_id,to_id);
    CREATE INDEX IF NOT EXISTS idx_messages_unread ON messages(to_id,is_read);
    CREATE INDEX IF NOT EXISTS idx_attempts_ip ON login_attempts(ip,created_at);
    ");
}

function schema_init_sqlite(PDO $pdo): void {
    $pdo->exec(
    "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL UNIQUE COLLATE NOCASE,
        password_hash TEXT NOT NULL, username TEXT NOT NULL UNIQUE COLLATE NOCASE,
        first_name TEXT NOT NULL, last_name TEXT,
        gender TEXT NOT NULL CHECK (gender IN ('male','female','other')),
        category TEXT NOT NULL DEFAULT 'single_m', orientation TEXT NOT NULL DEFAULT 'flexible',
        birth_date TEXT NOT NULL, city TEXT, country TEXT, zip TEXT,
        bio TEXT NOT NULL DEFAULT '', interests TEXT NOT NULL DEFAULT '', looking_for TEXT NOT NULL DEFAULT '',
        body_type TEXT, height_cm INTEGER, avatar TEXT NOT NULL DEFAULT '', cover TEXT NOT NULL DEFAULT '',
        is_active INTEGER NOT NULL DEFAULT 0, is_admin INTEGER NOT NULL DEFAULT 0,
        banned INTEGER NOT NULL DEFAULT 0, verified INTEGER NOT NULL DEFAULT 0,
        visibility TEXT NOT NULL DEFAULT 'members', online_last TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT);
    CREATE TABLE IF NOT EXISTS profile_photos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, photo TEXT NOT NULL,
        is_cover INTEGER NOT NULL DEFAULT 0, position INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);
    CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT, ip TEXT NOT NULL, identifier TEXT NOT NULL,
        success INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT (datetime('now')));
    CREATE TABLE IF NOT EXISTS likes (
        user_id INTEGER NOT NULL, target_id INTEGER NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now')), PRIMARY KEY (user_id,target_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE);
    CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT, from_id INTEGER NOT NULL, to_id INTEGER NOT NULL,
        body TEXT NOT NULL, is_read INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE);
    CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT, reporter INTEGER, target_id INTEGER NOT NULL,
        reason TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'open',
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (reporter) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE);
    CREATE TABLE IF NOT EXISTS uploads (
        id INTEGER PRIMARY KEY AUTOINCREMENT, filename TEXT NOT NULL UNIQUE, mime TEXT NOT NULL,
        data BLOB NOT NULL, size INTEGER NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')));
    CREATE INDEX IF NOT EXISTS idx_users_category ON users(category);
    CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active,banned);
    CREATE INDEX IF NOT EXISTS idx_users_gender ON users(gender);
    CREATE INDEX IF NOT EXISTS idx_photos_user ON profile_photos(user_id);
    CREATE INDEX IF NOT EXISTS idx_messages_pair ON messages(from_id,to_id);
    CREATE INDEX IF NOT EXISTS idx_messages_unread ON messages(to_id,is_read);
    CREATE INDEX IF NOT EXISTS idx_attempts_ip ON login_attempts(ip,created_at);
    ");
}

function schema_migrate(PDO $pdo): void {
    if (is_postgres()) return;
    try {
        $cols=$pdo->query('PRAGMA table_info(users)')->fetchAll();
        foreach($cols as $c) if($c['name']==='verified') return;
        $pdo->exec('ALTER TABLE users ADD COLUMN verified INTEGER NOT NULL DEFAULT 0');
    } catch (Throwable) {}
}

