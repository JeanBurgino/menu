<?php
/**
 * Bring! App Konfiguration
 * 
 * Diese Datei enthält die Einstellungen für die Bring! Integration
 * SICHERHEIT: Diese Datei enthält sensible Daten und sollte nicht ins Git-Repository!
 */

// ==================== BRING! LOGIN ====================
// Trage hier deine Bring! Login-Daten ein

define('BRING_EMAIL', 'c_jeanbourquin@bluewin.ch');
define('BRING_PASSWORD', 'Mj5696Cj');

// ==================== BRING! LISTEN ====================
// Name deiner Standard-Einkaufsliste in der Bring! App

define('BRING_LIST_NAME', 'Zuhause');

// ==================== API-MODUS ====================
// true  = Direkter API-Zugriff (EMPFOHLEN, wenn Login-Daten vorhanden)
// false = Deeplink-Methode (Fallback)

define('BRING_USE_API', true);

// ==================== ERWEITERTE EINSTELLUNGEN ====================

// API-Basis-URL (normalerweise nicht ändern)
define('BRING_API_URL', 'https://api.getbring.com/rest/v2');

// Request-Timeout in Sekunden
define('BRING_API_TIMEOUT', 30);

// Rate Limiting: Pause zwischen Requests in Millisekunden
define('BRING_RATE_LIMIT_MS', 100);

// Debug-Modus für Bring! API (Logs werden in logs/bring_api.log geschrieben)
define('BRING_DEBUG', false);

// ==================== VALIDIERUNG ====================

// Prüfe ob Pflicht-Einstellungen gesetzt sind
if (BRING_USE_API) {
    if (empty(BRING_EMAIL) || empty(BRING_PASSWORD)) {
        trigger_error(
            'Bring! API-Modus aktiviert, aber Email/Passwort fehlen in bring-config.php',
            E_USER_WARNING
        );
    }
}

if (empty(BRING_LIST_NAME)) {
    trigger_error(
        'BRING_LIST_NAME ist nicht gesetzt in bring-config.php',
        E_USER_WARNING
    );
}
