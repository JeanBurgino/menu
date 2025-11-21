<?php
/**
 * Konfigurationsdatei für Menüplaner
 * 
 * WICHTIG: Diese Datei darf NICHTS ausgeben (für JSON API)!
 */

// Verhindere jegliche Ausgabe
@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);

// === DATENBANK KONFIGURATION ===
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'food_db_user');
if (!defined('DB_PASS')) define('DB_PASS', 'frPrY7XUtayS4g0Agwdw');
if (!defined('DB_NAME')) define('DB_NAME', 'foodmenudb');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// === BRING! API KONFIGURATION ===
// Trage hier deine Bring! Credentials ein:

if (!defined('BRING_EMAIL')) {
    define('BRING_EMAIL', 'c_jeanbourquin@bluewin.ch');
}
if (!defined('BRING_PASSWORD')) {
    define('BRING_PASSWORD', 'Mj5696Cj');
}
if (!defined('BRING_LIST_NAME')) {
    define('BRING_LIST_NAME', 'Zuhause');
}
if (!defined('BRING_USE_API')) {
    define('BRING_USE_API', true);
}

// Erweiterte Bring! Einstellungen
if (!defined('BRING_API_URL')) {
    define('BRING_API_URL', 'https://api.getbring.com/rest/v2');
}
if (!defined('BRING_API_TIMEOUT')) {
    define('BRING_API_TIMEOUT', 30);
}
if (!defined('BRING_RATE_LIMIT_MS')) {
    define('BRING_RATE_LIMIT_MS', 100);
}
if (!defined('BRING_DEBUG')) {
    define('BRING_DEBUG', false); // false in Produktion!
}

// === NOTION API KONFIGURATION ===
// Trage hier deine Notion API Credentials ein:
// 1. Erstelle eine Integration unter https://www.notion.so/my-integrations
// 2. Kopiere das "Internal Integration Token"
// 3. Teile deine Datenbank mit der Integration
// 4. Kopiere die Database ID aus der Datenbank-URL

if (!defined('NOTION_API_TOKEN')) {
    define('NOTION_API_TOKEN', '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'); // Dein Notion Integration Token hier eintragen
}
if (!defined('NOTION_DATABASE_ID')) {
    define('NOTION_DATABASE_ID', '2b26f3f4f91680bfabd4f99a1aacee4d'); // Deine Notion Database ID hier eintragen
}

// Erweiterte Notion Einstellungen
if (!defined('NOTION_API_URL')) {
    define('NOTION_API_URL', 'https://api.notion.com/v1');
}
if (!defined('NOTION_API_VERSION')) {
    define('NOTION_API_VERSION', '2022-06-28'); // Notion API Version
}
if (!defined('NOTION_API_TIMEOUT')) {
    define('NOTION_API_TIMEOUT', 30);
}
if (!defined('NOTION_DEBUG')) {
    define('NOTION_DEBUG', false); // false in Produktion!
}

// === ANWENDUNGS-EINSTELLUNGEN ===
if (!defined('APP_NAME')) define('APP_NAME', 'Menüplaner');
if (!defined('APP_VERSION')) define('APP_VERSION', '2.0.0');
if (!defined('APP_TIMEZONE')) define('APP_TIMEZONE', 'Europe/Zurich');

// === DEBUG MODUS ===
// Aktiviere DEBUG_MODE für zusätzliche Bestätigungsdialoge
// true = Zeige Bestätigungsdialoge (z.B. "Möchten Sie wirklich...")
// false = Keine Bestätigungsdialoge, direkte Aktion
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

// === WOCHENPLAN-EINSTELLUNGEN ===
// Standard-Abendessen für Montag bis Freitag (Menu-ID des Rezepts)
// Trage hier die ID des gewünschten Standard-Abendessens ein
if (!defined('DEFAULT_WEEKDAY_DINNER_RECIPE_ID')) define('DEFAULT_WEEKDAY_DINNER_RECIPE_ID', 11);

// === PFADE ===
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__FILE__));
if (!defined('EXPORTS_PATH')) define('EXPORTS_PATH', BASE_PATH . '/exports');
if (!defined('BRING_EXPORTS_PATH')) define('BRING_EXPORTS_PATH', EXPORTS_PATH . '/bring');
if (!defined('LOGS_PATH')) define('LOGS_PATH', BASE_PATH . '/logs');

// === FEHLERBEHANDLUNG ===
$isProduction = (getenv('APP_ENV') === 'production');

if ($isProduction) {
    @ini_set('display_errors', 0);
    @ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
} else {
    // Auch in Development: Keine Ausgabe in API-Dateien!
    // Fehler nur loggen, nicht anzeigen
    @ini_set('display_errors', 0);
    @ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
}

@ini_set('log_errors', 1);
@ini_set('error_log', LOGS_PATH . '/php_errors.log');

// === SICHERHEITS-EINSTELLUNGEN ===
// Session-Sicherheit - nur wenn noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    @ini_set('session.cookie_secure', $isProduction ? 1 : 0);
}

// === TIMEZONE ===
@date_default_timezone_set(APP_TIMEZONE);

// === VERZEICHNISSE ERSTELLEN ===
$directories = [EXPORTS_PATH, BRING_EXPORTS_PATH, LOGS_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// === AUTOLOADER ===
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Unterdrücke Warnings von bring-config.php falls vorhanden
error_reporting(error_reporting() & ~E_WARNING & ~E_NOTICE);
