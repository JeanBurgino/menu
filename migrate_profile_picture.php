<?php
/**
 * Migration: Erweitere profile_picture Spalte von VARCHAR(255) auf TEXT
 *
 * Dieses Skript muss einmalig ausgeführt werden, um bestehende Datenbanken zu aktualisieren.
 * Base64-kodierte Bilder benötigen mehr als 255 Zeichen Speicherplatz.
 *
 * Aufruf: php migrate_profile_picture.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

try {
    $db = Database::getInstance();

    echo "Starte Migration: profile_picture VARCHAR(255) -> TEXT\n";
    echo "====================================================\n\n";

    // Prüfe aktuelle Spaltenstruktur
    $result = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'profile_picture'");

    if (empty($result)) {
        echo "FEHLER: Spalte 'profile_picture' existiert nicht in der Tabelle 'users'\n";
        exit(1);
    }

    $currentType = $result[0]['Type'];
    echo "Aktueller Datentyp: {$currentType}\n";

    // Führe Migration durch, falls nötig
    if (stripos($currentType, 'varchar') !== false) {
        echo "Führe ALTER TABLE durch...\n";

        $db->execute("ALTER TABLE users MODIFY COLUMN profile_picture TEXT DEFAULT NULL");

        echo "✓ Migration erfolgreich abgeschlossen!\n";
        echo "  profile_picture ist jetzt vom Typ TEXT\n\n";

        // Verifiziere Änderung
        $result = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'profile_picture'");
        $newType = $result[0]['Type'];
        echo "Neuer Datentyp: {$newType}\n";

    } else if (stripos($currentType, 'text') !== false) {
        echo "✓ Spalte ist bereits vom Typ TEXT - keine Migration nötig\n";
    } else {
        echo "WARNUNG: Unerwarteter Datentyp '{$currentType}'\n";
        echo "Bitte manuell prüfen!\n";
        exit(1);
    }

    echo "\n====================================================\n";
    echo "Migration abgeschlossen.\n";

} catch (Exception $e) {
    echo "\nFEHLER bei der Migration:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
