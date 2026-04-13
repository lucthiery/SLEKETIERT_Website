<?php
/**
 * ONE-TIME SETUP SCRIPT
 * Run once to create the SQLite database and seed all users.
 * DELETE THIS FILE after running it!
 *
 * Access: https://selektiert.com/admin/setup.php?key=SETUP_SELEKTIERT_2026
 */

define('SETUP_KEY', 'SETUP_SELEKTIERT_2026');

if (($_GET['key'] ?? '') !== SETUP_KEY) {
    http_response_code(403);
    die('Forbidden. Provide ?key= parameter.');
}

define('DB_DIR',  '/var/data/selektiert');
define('DB_PATH', DB_DIR . '/selektiert.db');

// Ensure DB directory exists
if (!is_dir(DB_DIR)) {
    mkdir(DB_DIR, 0750, true);
}

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA journal_mode=WAL');

// Create table
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name       TEXT NOT NULL,
    last_name        TEXT NOT NULL,
    email            TEXT UNIQUE NOT NULL,
    birthdate        TEXT,
    profile_picture  TEXT,
    password_hash    TEXT NOT NULL,
    role             TEXT DEFAULT 'Mitglied',
    is_admin         INTEGER DEFAULT 0,
    reset_token      TEXT,
    reset_expires    INTEGER,
    created_at       TEXT DEFAULT (datetime('now'))
)
");

// Seed users from selektiert_emails.csv
// Format: first_name, last_name, email, temp_password, role, is_admin
$users = [
    ['Luc',           'Thiery',              'luc.thiery@selektiert.com',              'Luc#2026!Sel',        'Kassier',            1],
    ['Anton',         'Campman',             'anton.campman@selektiert.com',           'Anton#2026!Sel',      'Obmann',             1],
    ['Magdalena Kim', 'Novak',               'magdalena.novak@selektiert.com',         'Magdalena#2026!Sel',  'Mitglied',           0],
    ['Albert',        'Eichler',             'albert.eichler@selektiert.com',          'Albert#2026!Sel',     'Rechnungsprüfer',    0],
    ['Carlo',         'Morassutti-Vitale',   'carlo.morassutti-vitale@selektiert.com', 'Carlo#2026!Sel',      'Mitglied',           0],
    ['Alexander',     'Heinke',              'alexander.heinke@selektiert.com',        'Alexander#2026!Sel',  'Mitglied',           0],
    ['Moritz',        'Mader',               'moritz.mader@selektiert.com',            'Moritz#2026!Sel',     'Mitglied',           0],
    ['Sansani',       'Bauer',               'sansani.bauer@selektiert.com',           'Sansani#2026!Sel',    'Mitglied',           0],
    ['Johnbosco',     'Madueke',             'johnbosco.madueke@selektiert.com',       'Johnbosco#2026!Sel',  'Mitglied',           0],
    ['Paul',          'Zechner',             'paul.zechner@selektiert.com',            'PaulZ#2026!Sel',      'Stv. Obmann',        0],
    ['Ben',           'Yosfan',              'ben.yosfan@selektiert.com',              'Ben#2026!Sel',        '2. Rechnungsprüfer', 0],
    ['Lara',          'Masliah',             'lara.masliah@selektiert.com',            'Lara#2026!Sel',       'Mitglied',           0],
    ['Kristina',      'Meniga',              'kristina.meniga@selektiert.com',         'Kristina#2026!Sel',   'Mitglied',           0],
    ['Johannes',      'Pixner',              'johannes.pixner@selektiert.com',         'Johannes#2026!Sel',   'Mitglied',           0],
    ['Paul',          'Schurich',            'paul.schurich@selektiert.com',           'PaulS#2026!Sel',      'Mitglied',           0],
    ['Ron',           'Feldman',             'ron.feldman@selektiert.com',             'Ron#2026!Sel',        'Mitglied',           0],
    ['Sansi',         'Bauer',               'sansi.bauer@selektiert.com',             'Sansi#2026!Sel',      'Mitglied',           0],
    ['Anna',          'Cieslar',             'anna.cieslar@selektiert.com',            'Anna#2026!Sel',       'Mitglied',           0],
    ['Theresa',       'Komornyik',           'theresa.komornyik@selektiert.com',       'Theresa#2026!Sel',    'Mitglied',           0],
    ['Pia',           'Frank',               'pia.frank@selektiert.com',               'Pia#2026!Sel',        'Mitglied',           0],
    // mailadmin system account
    ['Mail',          'Admin',               'mailadmin@selektiert.com',               'MailAdmin2026!',      'System',             1],
];

$stmt    = $pdo->prepare('INSERT OR IGNORE INTO users (first_name, last_name, email, password_hash, role, is_admin) VALUES (?,?,?,?,?,?)');
$created = 0;
$skipped = 0;

foreach ($users as [$fn, $ln, $email, $pw, $role, $admin]) {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt->execute([$fn, $ln, $email, $hash, $role, $admin]);
    if ($stmt->rowCount() > 0) { $created++; } else { $skipped++; }
}

chmod(DB_PATH, 0640);

echo "<pre style='font-family:monospace;background:#111;color:#ffe137;padding:2rem;'>";
echo "=== SELEKTIERT DB SETUP ===\n\n";
echo "DB path : " . DB_PATH . "\n";
echo "Created : $created users\n";
echo "Skipped : $skipped (already existed)\n\n";

$rows = $pdo->query('SELECT id, first_name, last_name, email, role, is_admin FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
echo str_pad('ID',4) . str_pad('Name',30) . str_pad('Email',45) . str_pad('Role',25) . "Admin\n";
echo str_repeat('-', 110) . "\n";
foreach ($rows as $r) {
    echo str_pad($r['id'], 4)
       . str_pad($r['first_name'] . ' ' . $r['last_name'], 30)
       . str_pad($r['email'], 45)
       . str_pad($r['role'], 25)
       . ($r['is_admin'] ? 'YES' : '—') . "\n";
}

echo "\n\n⚠  DELETE THIS FILE NOW: rm /var/www/selektiert.com/admin/setup.php\n";
echo "</pre>";
