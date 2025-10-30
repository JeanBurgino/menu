<?php
/**
 * Bring! Integration Debug Tool
 * 
 * Testet die gesamte Bring! Integration und zeigt detaillierte Fehler
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Bring! Debug</title><style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
.success { color: #4CAF50; font-weight: bold; }
.error { color: #f44336; font-weight: bold; }
.warning { color: #ff9800; font-weight: bold; }
.info { color: #2196F3; }
pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-left: 3px solid #2196F3; }
.log-entry { margin: 5px 0; padding: 5px; border-left: 3px solid #ddd; }
.log-ERROR { border-left-color: #f44336; background: #ffebee; }
.log-WARNING { border-left-color: #ff9800; background: #fff3e0; }
.log-INFO { border-left-color: #2196F3; background: #e3f2fd; }
</style></head><body>";

echo "<h1>🔍 Bring! Integration Debug</h1>";

// ==================== CONFIG ====================
echo "<div class='section'>";
echo "<h2>1️⃣ Konfiguration</h2>";

if (!file_exists('config.php')) {
    echo "<p class='error'>❌ config.php nicht gefunden!</p>";
    exit;
}
require_once 'config.php';
echo "<p class='success'>✅ config.php geladen</p>";

if (!file_exists('bring-config.php')) {
    echo "<p class='error'>❌ bring-config.php nicht gefunden!</p>";
    exit;
}
require_once 'bring-config.php';
echo "<p class='success'>✅ bring-config.php geladen</p>";

echo "<h3>Einstellungen:</h3>";
echo "<ul>";
echo "<li>BRING_EMAIL: " . (defined('BRING_EMAIL') && !empty(BRING_EMAIL) ? '<span class="success">✅ Gesetzt</span>' : '<span class="error">❌ LEER</span>') . "</li>";
echo "<li>BRING_PASSWORD: " . (defined('BRING_PASSWORD') && !empty(BRING_PASSWORD) ? '<span class="success">✅ Gesetzt</span>' : '<span class="error">❌ LEER</span>') . "</li>";
echo "<li>BRING_LIST_NAME: <strong>" . (defined('BRING_LIST_NAME') ? BRING_LIST_NAME : '<span class="error">NICHT DEFINIERT</span>') . "</strong></li>";
echo "<li>BRING_USE_API: " . (defined('BRING_USE_API') && BRING_USE_API ? '<span class="success">✅ Aktiv</span>' : '<span class="warning">⚠️  Deaktiviert</span>') . "</li>";
echo "</ul>";
echo "</div>";

if (!defined('BRING_USE_API') || !BRING_USE_API) {
    echo "<div class='section'><p class='warning'>⚠️  BRING_USE_API ist deaktiviert. Setze in bring-config.php:<br><code>define('BRING_USE_API', true);</code></p></div>";
    exit;
}

if (empty(BRING_EMAIL) || empty(BRING_PASSWORD)) {
    echo "<div class='section'><p class='error'>❌ BRING_EMAIL oder BRING_PASSWORD ist leer!<br>Bitte in bring-config.php eintragen.</p></div>";
    exit;
}

// ==================== BRINGAPI ====================
echo "<div class='section'>";
echo "<h2>2️⃣ BringAPI Klasse</h2>";

if (!file_exists('classes/BringAPI.php')) {
    echo "<p class='error'>❌ classes/BringAPI.php nicht gefunden!</p>";
    echo "<p>Bitte erstelle das Verzeichnis 'classes/' und lade BringAPI.php hoch.</p>";
    exit;
}
require_once 'classes/BringAPI.php';
echo "<p class='success'>✅ BringAPI.php geladen</p>";
echo "</div>";

// ==================== LOGIN TEST ====================
echo "<div class='section'>";
echo "<h2>3️⃣ Login Test</h2>";

try {
    $bring = new BringAPI(BRING_EMAIL, BRING_PASSWORD, ['debug' => true]);
    echo "<p class='info'>→ Sende Login-Request...</p>";
    
    $loginResult = $bring->login();
    
    if ($loginResult) {
        echo "<p class='success'>✅ Login erfolgreich!</p>";
        
        $userInfo = $bring->getUserInfo();
        echo "<h3>Benutzer-Info:</h3>";
        echo "<ul>";
        echo "<li>Name: <strong>{$userInfo['name']}</strong></li>";
        echo "<li>UUID: <code>{$userInfo['uuid']}</code></li>";
        echo "<li>Email: {$userInfo['email']}</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Login fehlgeschlagen!</p>";
        echo "<p>Fehler: " . $bring->getLastError() . "</p>";
        echo "<p><strong>Mögliche Ursachen:</strong></p>";
        echo "<ul>";
        echo "<li>Email oder Passwort falsch</li>";
        echo "<li>Bring! Account existiert nicht</li>";
        echo "<li>Bring! API ist nicht erreichbar</li>";
        echo "</ul>";
        echo "</div>";
        
        // Zeige Log
        showLog();
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "</div>";
    showLog();
    exit;
}
echo "</div>";

// ==================== LISTEN LADEN ====================
echo "<div class='section'>";
echo "<h2>4️⃣ Listen Laden</h2>";

try {
    echo "<p class='info'>→ Lade Listen...</p>";
    $lists = $bring->getLists();
    
    if (isset($lists['error'])) {
        echo "<p class='error'>❌ Fehler beim Laden: {$lists['error']}</p>";
        echo "</div>";
        showLog();
        exit;
    }
    
    if (isset($lists['lists']) && count($lists['lists']) > 0) {
        echo "<p class='success'>✅ " . count($lists['lists']) . " Listen gefunden</p>";
        
        echo "<h3>Verfügbare Listen:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th style='padding: 8px;'>Name</th><th style='padding: 8px;'>UUID</th><th style='padding: 8px;'>Status</th></tr>";
        
        $configuredListFound = false;
        foreach ($lists['lists'] as $list) {
            $isConfigured = ($list['name'] === BRING_LIST_NAME);
            if ($isConfigured) $configuredListFound = true;
            
            $rowStyle = $isConfigured ? "background: #c8e6c9;" : "";
            $status = $isConfigured ? "⭐ KONFIGURIERT" : "";
            
            echo "<tr style='{$rowStyle}'>";
            echo "<td style='padding: 8px;'><strong>{$list['name']}</strong></td>";
            echo "<td style='padding: 8px; font-family: monospace;'>{$list['listUuid']}</td>";
            echo "<td style='padding: 8px;'>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (!$configuredListFound) {
            echo "<p class='error'>❌ Konfigurierte Liste '<strong>" . BRING_LIST_NAME . "</strong>' nicht gefunden!</p>";
            echo "<p><strong>Lösung:</strong> Ändere BRING_LIST_NAME in bring-config.php zu einer der obigen Listen.</p>";
            echo "</div>";
            showLog();
            exit;
        } else {
            echo "<p class='success'>✅ Konfigurierte Liste gefunden!</p>";
        }
    } else {
        echo "<p class='warning'>⚠️  Keine Listen gefunden</p>";
        echo "<p>Erstelle eine Liste in der Bring! App und versuche es erneut.</p>";
        echo "</div>";
        showLog();
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "</div>";
    showLog();
    exit;
}
echo "</div>";

// ==================== TEST ARTIKEL ====================
echo "<div class='section'>";
echo "<h2>5️⃣ Test-Artikel hinzufügen</h2>";

try {
    $list = $bring->getListByName(BRING_LIST_NAME);
    
    if (!$list) {
        echo "<p class='error'>❌ Liste konnte nicht gefunden werden!</p>";
        echo "</div>";
        showLog();
        exit;
    }
    
    echo "<p class='info'>→ Füge Test-Artikel hinzu...</p>";
    
    $testItems = [
        ['name' => 'Mehl', 'specification' => '500g (Debug-Test)'],
        ['name' => 'Milch', 'specification' => '1L (Debug-Test)']
    ];
    
    $result = $bring->batchAddItems($list['listUuid'], $testItems);
    
    echo "<h3>Ergebnis:</h3>";
    echo "<ul>";
    echo "<li>Total: {$result['total']}</li>";
    echo "<li>Erfolgreich: <span class='success'>{$result['successful']}</span></li>";
    echo "<li>Fehlgeschlagen: " . ($result['failed'] > 0 ? "<span class='error'>{$result['failed']}</span>" : "<span class='success'>0</span>") . "</li>";
    echo "</ul>";
    
    if ($result['failed'] > 0) {
        echo "<h3>Fehler-Details:</h3>";
        echo "<pre>" . print_r($result['errors'], true) . "</pre>";
        
        echo "<h3>HTTP Responses:</h3>";
        foreach ($result['results'] as $r) {
            if (!$r['success']) {
                echo "<div class='log-ERROR'>";
                echo "<strong>{$r['item']}</strong><br>";
                echo "HTTP Code: {$r['http_code']}<br>";
                echo "Error: " . ($r['error'] ?? 'Unknown') . "<br>";
                echo "</div>";
            }
        }
        
        echo "<div style='margin-top: 20px; padding: 15px; background: #fff3e0; border-left: 4px solid #ff9800;'>";
        echo "<h3>🔧 Fehleranalyse HTTP 400:</h3>";
        echo "<p><strong>HTTP 400 Bad Request</strong> bedeutet, dass die Bring! API die Request nicht akzeptiert.</p>";
        echo "<p><strong>Mögliche Ursachen:</strong></p>";
        echo "<ol>";
        echo "<li><strong>Token abgelaufen:</strong> Versuche es nochmal (Token wird automatisch erneuert)</li>";
        echo "<li><strong>Falsches Request-Format:</strong> Body-Parameter stimmen nicht</li>";
        echo "<li><strong>Liste existiert nicht:</strong> UUID ist ungültig</li>";
        echo "<li><strong>Account-Berechtigung:</strong> Kein Zugriff auf die Liste</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<p class='success'>✅ Alle Test-Artikel erfolgreich hinzugefügt!</p>";
        echo "<p><strong>Prüfe jetzt die Bring! App - die Artikel sollten in der Liste '" . BRING_LIST_NAME . "' sein.</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ==================== LOG ANZEIGEN ====================
showLog();

// ==================== ZUSAMMENFASSUNG ====================
echo "<div class='section'>";
echo "<h2>📊 Zusammenfassung</h2>";

if ($result['failed'] === 0) {
    echo "<p class='success' style='font-size: 1.2em;'>🎉 Alle Tests erfolgreich! Die Bring! Integration funktioniert.</p>";
    echo "<p>Du kannst jetzt im Menüplaner auf den <strong>Bring!</strong> Button klicken.</p>";
} else {
    echo "<p class='error' style='font-size: 1.2em;'>⚠️  Es gibt Probleme mit der Bring! Integration.</p>";
    echo "<p><strong>Nächste Schritte:</strong></p>";
    echo "<ol>";
    echo "<li>Prüfe das Log oben für Details</li>";
    echo "<li>Stelle sicher, dass Email/Passwort korrekt sind</li>";
    echo "<li>Prüfe ob die Liste in der Bring! App existiert</li>";
    echo "<li>Versuche es in 5 Minuten nochmal (Token-Reset)</li>";
    echo "</ol>";
}

echo "</div>";

echo "</body></html>";

// ==================== HELPER ====================
function showLog() {
    echo "<div class='section'>";
    echo "<h2>📝 Detailliertes Log</h2>";
    
    if (defined('LOGS_PATH') && file_exists(LOGS_PATH . '/bring_api.log')) {
        $log = file_get_contents(LOGS_PATH . '/bring_api.log');
        $lines = explode("\n", $log);
        
        // Nur die letzten 50 Zeilen
        $lines = array_slice($lines, -50);
        
        echo "<div style='max-height: 400px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $class = 'log-entry';
            if (strpos($line, '[ERROR]') !== false) {
                $class .= ' log-ERROR';
            } elseif (strpos($line, '[WARNING]') !== false) {
                $class .= ' log-WARNING';
            } elseif (strpos($line, '[INFO]') !== false) {
                $class .= ' log-INFO';
            }
            
            echo "<div class='{$class}'>" . htmlspecialchars($line) . "</div>";
        }
        echo "</div>";
    } else {
        echo "<p class='warning'>⚠️  Kein Log gefunden (LOGS_PATH nicht definiert oder Datei existiert nicht)</p>";
    }
    
    echo "</div>";
}
