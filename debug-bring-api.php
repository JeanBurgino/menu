<?php
/**
 * Debug Script für Bring! API
 * 
 * Testet die Konfiguration und API-Verbindung
 * Optimierte Version mit besserer Ausgabe
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔═══════════════════════════════════════════════════╗\n";
echo "║       Bring! API Debug & Diagnose v2.0           ║\n";
echo "╚═══════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];

// ==================== 1. KONFIGURATION PRÜFEN ====================

echo "📋 1. Prüfe Konfigurationsdateien...\n";
echo str_repeat("─", 50) . "\n";

// config.php
if (file_exists('config.php')) {
    echo "   ✅ config.php gefunden\n";
    require_once 'config.php';
} else {
    echo "   ❌ config.php nicht gefunden!\n";
    $errors[] = "config.php fehlt";
}

// bring-config.php
if (file_exists('bring-config.php')) {
    echo "   ✅ bring-config.php gefunden\n";
    require_once 'bring-config.php';
} else {
    echo "   ❌ bring-config.php nicht gefunden!\n";
    $errors[] = "bring-config.php fehlt";
}

echo "\n";

// ==================== 2. KONSTANTEN PRÜFEN ====================

echo "⚙️  2. Prüfe Konfigurationswerte...\n";
echo str_repeat("─", 50) . "\n";

$config_checks = [
    'BRING_EMAIL' => 'Bring! E-Mail',
    'BRING_PASSWORD' => 'Bring! Passwort',
    'BRING_LIST_NAME' => 'Listen-Name',
    'BRING_USE_API' => 'API-Modus'
];

foreach ($config_checks as $const => $label) {
    if (defined($const)) {
        $value = constant($const);
        if ($const === 'BRING_PASSWORD' && !empty($value)) {
            $masked = str_repeat('*', min(strlen($value), 8));
            echo "   ✅ {$label}: {$masked}\n";
        } elseif ($const === 'BRING_EMAIL' && !empty($value)) {
            $parts = explode('@', $value);
            $masked = substr($parts[0], 0, 3) . '***@' . $parts[1];
            echo "   ✅ {$label}: {$masked}\n";
        } elseif ($const === 'BRING_USE_API') {
            $status = $value ? 'aktiviert' : 'deaktiviert';
            echo "   ✅ {$label}: {$status}\n";
        } else {
            echo "   ✅ {$label}: " . ($value ?: 'LEER') . "\n";
        }
        
        if (empty($value) && $const !== 'BRING_USE_API') {
            $warnings[] = "{$label} ist leer";
        }
    } else {
        echo "   ❌ {$label}: nicht definiert\n";
        $errors[] = "{$const} ist nicht definiert";
    }
}

echo "\n";

// ==================== 3. PHP EXTENSIONS PRÜFEN ====================

echo "🔧 3. Prüfe PHP Extensions...\n";
echo str_repeat("─", 50) . "\n";

$required_extensions = [
    'curl' => 'cURL (für HTTP Requests)',
    'json' => 'JSON (für Datenverarbeitung)',
    'pdo' => 'PDO (für Datenbank)',
    'pdo_mysql' => 'PDO MySQL (für MySQL)'
];

foreach ($required_extensions as $ext => $description) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$description}\n";
    } else {
        echo "   ❌ {$description} fehlt!\n";
        $errors[] = "PHP Extension '{$ext}' fehlt";
    }
}

echo "\n";

// ==================== 4. DATEIEN PRÜFEN ====================

echo "📁 4. Prüfe erforderliche Dateien...\n";
echo str_repeat("─", 50) . "\n";

$required_files = [
    'classes/BringAPI.php' => 'Bring! API Klasse',
    'classes/Database.php' => 'Datenbank Klasse',
    'api.php' => 'API Endpoint'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$description}: {$file}\n";
        
        // Syntax-Check
        $output = [];
        $return = 0;
        exec("php -l {$file} 2>&1", $output, $return);
        if ($return !== 0) {
            echo "      ⚠️  Syntax-Fehler gefunden!\n";
            $warnings[] = "Syntax-Fehler in {$file}";
        }
    } else {
        echo "   ❌ {$description}: {$file} fehlt!\n";
        $errors[] = "{$file} fehlt";
    }
}

echo "\n";

// ==================== 5. VERZEICHNISSE PRÜFEN ====================

echo "📂 5. Prüfe Verzeichnisse...\n";
echo str_repeat("─", 50) . "\n";

$required_dirs = [
    'exports/bring' => 'Bring! Exports',
    'logs' => 'Log-Dateien',
    'classes' => 'PHP Klassen'
];

foreach ($required_dirs as $dir => $description) {
    if (is_dir($dir)) {
        echo "   ✅ {$description}: /{$dir}\n";
        
        // Schreibrechte prüfen
        if (is_writable($dir)) {
            echo "      ✅ Schreibrechte OK\n";
        } else {
            echo "      ⚠️  Keine Schreibrechte!\n";
            $warnings[] = "Keine Schreibrechte für {$dir}";
        }
    } else {
        echo "   ⚠️  {$description}: /{$dir} fehlt\n";
        echo "      💡 Erstelle mit: mkdir -p {$dir}\n";
        $warnings[] = "{$dir} fehlt";
    }
}

echo "\n";

// ==================== 6. BRING! API TEST ====================

if (defined('BRING_USE_API') && BRING_USE_API && 
    defined('BRING_EMAIL') && !empty(BRING_EMAIL) && 
    defined('BRING_PASSWORD') && !empty(BRING_PASSWORD)) {
    
    echo "🔌 6. Teste Bring! API Verbindung...\n";
    echo str_repeat("─", 50) . "\n";
    
    if (!file_exists('classes/BringAPI.php')) {
        echo "   ❌ BringAPI.php fehlt!\n\n";
    } else {
        require_once 'classes/BringAPI.php';
        
        try {
            $bring = new BringAPI(BRING_EMAIL, BRING_PASSWORD);
            
            echo "   → Sende Login-Request...\n";
            $loginResult = $bring->login();
            
            if ($loginResult) {
                echo "   ✅ Login erfolgreich!\n\n";
                
                $userInfo = $bring->getUserInfo();
                echo "   👤 Benutzer-Info:\n";
                echo "      Name: {$userInfo['name']}\n";
                echo "      UUID: {$userInfo['uuid']}\n\n";
                
                echo "   → Lade Listen...\n";
                $lists = $bring->getLists();
                
                if (isset($lists['lists'])) {
                    echo "   ✅ Listen erfolgreich geladen!\n\n";
                    echo "   📋 Verfügbare Listen:\n";
                    
                    $found = false;
                    foreach ($lists['lists'] as $list) {
                        $marker = ($list['name'] === BRING_LIST_NAME) ? ' ⭐ KONFIGURIERT' : '';
                        echo "      • {$list['name']}{$marker}\n";
                        echo "        UUID: {$list['listUuid']}\n";
                        
                        if ($list['name'] === BRING_LIST_NAME) {
                            $found = true;
                        }
                    }
                    
                    echo "\n";
                    
                    if (!$found) {
                        echo "   ⚠️  Konfigurierte Liste '" . BRING_LIST_NAME . "' nicht gefunden!\n";
                        echo "      💡 Bitte BRING_LIST_NAME in bring-config.php anpassen\n\n";
                        $warnings[] = "Konfigurierte Liste nicht gefunden";
                    } else {
                        echo "   ✅ Konfigurierte Liste gefunden und einsatzbereit!\n\n";
                    }
                } else {
                    echo "   ⚠️  Keine Listen gefunden\n\n";
                    $warnings[] = "Keine Listen in Bring! Account";
                }
                
                // Test-Export (optional)
                echo "   🧪 Möchtest du einen Test-Artikel hinzufügen? (y/n): ";
                $handle = fopen("php://stdin", "r");
                $line = fgets($handle);
                if (trim($line) === 'y') {
                    $list = $bring->getListByName(BRING_LIST_NAME);
                    if ($list) {
                        $result = $bring->saveItem($list['listUuid'], 'Test-Artikel (Debug)', 'von Debug-Script');
                        if ($result['success']) {
                            echo "   ✅ Test-Artikel erfolgreich hinzugefügt!\n";
                        } else {
                            echo "   ❌ Fehler beim Hinzufügen: " . ($result['error'] ?? 'Unknown') . "\n";
                        }
                    }
                }
                echo "\n";
                
            } else {
                echo "   ❌ Login fehlgeschlagen!\n";
                echo "      💡 Prüfe Email und Passwort in bring-config.php\n\n";
                $errors[] = "Bring! Login fehlgeschlagen";
            }
        } catch (Exception $e) {
            echo "   ❌ Fehler: " . $e->getMessage() . "\n\n";
            $errors[] = "Bring! API Fehler: " . $e->getMessage();
        }
    }
} else {
    echo "⏭️  6. Bring! API Test übersprungen\n";
    echo str_repeat("─", 50) . "\n";
    echo "   API-Modus ist deaktiviert oder Login-Daten fehlen\n";
    echo "   → Deeplink-Methode wird verwendet\n\n";
}

// ==================== 7. ZUSAMMENFASSUNG ====================

echo "╔═══════════════════════════════════════════════════╗\n";
echo "║                  ZUSAMMENFASSUNG                  ║\n";
echo "╚═══════════════════════════════════════════════════╝\n\n";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "🎉 Alle Tests erfolgreich!\n";
    echo "✅ Deine Bring! Integration ist einsatzbereit!\n\n";
} else {
    if (count($errors) > 0) {
        echo "❌ FEHLER (" . count($errors) . "):\n";
        foreach ($errors as $error) {
            echo "   • {$error}\n";
        }
        echo "\n";
    }
    
    if (count($warnings) > 0) {
        echo "⚠️  WARNUNGEN (" . count($warnings) . "):\n";
        foreach ($warnings as $warning) {
            echo "   • {$warning}\n";
        }
        echo "\n";
    }
    
    if (count($errors) > 0) {
        echo "🔧 Bitte behebe die Fehler bevor du fortfährst.\n\n";
    } else {
        echo "ℹ️  Die App funktioniert, aber einige Optimierungen sind empfohlen.\n\n";
    }
}

echo "📚 Mehr Infos: README.md\n";
echo "💬 Support: Prüfe /logs/ für Details\n\n";

exit(count($errors) > 0 ? 1 : 0);
