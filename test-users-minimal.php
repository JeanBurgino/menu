<?php
/**
 * Minimal Test API - User Laden
 * 
 * Verwendung: Öffne im Browser um zu testen ob DB-Verbindung funktioniert
 * URL: http://deine-domain.com/menueplaner/test-users-minimal.php
 */

// Fehler anzeigen
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON Header
header('Content-Type: application/json; charset=utf-8');

echo "{\n";
echo '  "test": "API Test gestartet",'."\n";

// Test 1: Config laden
try {
    if (!file_exists('config.php')) {
        throw new Exception('config.php nicht gefunden!');
    }
    require_once 'config.php';
    echo '  "config": "✅ config.php geladen",'."\n";
} catch (Exception $e) {
    echo '  "config": "❌ ' . $e->getMessage() . '",'."\n";
    echo '  "error": "Config konnte nicht geladen werden"'."\n";
    echo "}";
    exit;
}

// Test 2: DB-Konstanten prüfen
echo '  "db_check": {'."\n";
echo '    "DB_HOST": "' . (defined('DB_HOST') ? '✅ definiert' : '❌ fehlt') . '",'."\n";
echo '    "DB_USER": "' . (defined('DB_USER') ? '✅ definiert' : '❌ fehlt') . '",'."\n";
echo '    "DB_PASS": "' . (defined('DB_PASS') ? '✅ definiert' : '❌ fehlt') . '",'."\n";
echo '    "DB_NAME": "' . (defined('DB_NAME') ? '✅ definiert' : '❌ fehlt') . '"'."\n";
echo '  },'."\n";

// Test 3: DB-Verbindung
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo '  "database": "✅ Verbindung erfolgreich",'."\n";
} catch (PDOException $e) {
    echo '  "database": "❌ Verbindung fehlgeschlagen: ' . $e->getMessage() . '",'."\n";
    echo '  "error": "Datenbank-Verbindung fehlgeschlagen"'."\n";
    echo "}";
    exit;
}

// Test 4: User-Tabelle prüfen
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo '  "table_users": "✅ Tabelle existiert",'."\n";
    } else {
        echo '  "table_users": "❌ Tabelle existiert nicht - database_schema.sql ausführen!",'."\n";
        echo '  "error": "User-Tabelle nicht gefunden"'."\n";
        echo "}";
        exit;
    }
} catch (Exception $e) {
    echo '  "table_users": "❌ Fehler: ' . $e->getMessage() . '",'."\n";
}

// Test 5: User laden
try {
    $stmt = $pdo->query("SELECT id, name, profile_image, profile_picture, is_admin FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '  "user_count": ' . count($users) . ','."\n";
    echo '  "users": ' . json_encode($users, JSON_UNESCAPED_UNICODE) . ','."\n";
    
    if (count($users) === 0) {
        echo '  "warning": "⚠️  Keine User in Datenbank - database_schema.sql importieren!",'."\n";
    } else {
        echo '  "status": "✅ Alle Tests erfolgreich!",'."\n";
    }
    
} catch (Exception $e) {
    echo '  "users": "❌ Fehler beim Laden: ' . $e->getMessage() . '",'."\n";
    echo '  "error": "User konnten nicht geladen werden"'."\n";
}

// Test 6: PHP Info
echo '  "php_version": "' . phpversion() . '",'."\n";
echo '  "extensions": {'."\n";
echo '    "pdo": ' . (extension_loaded('pdo') ? 'true' : 'false') . ','."\n";
echo '    "pdo_mysql": ' . (extension_loaded('pdo_mysql') ? 'true' : 'false') . ','."\n";
echo '    "curl": ' . (extension_loaded('curl') ? 'true' : 'false') . ','."\n";
echo '    "json": ' . (extension_loaded('json') ? 'true' : 'false') . "\n";
echo '  },'."\n";

echo '  "success": true'."\n";
echo "}";
