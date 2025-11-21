# Notion Integration - Setup Anleitung

## Übersicht

Die Notion-Integration ermöglicht es, Ihren Wochenplan direkt aus dem Menüplaner an eine Notion-Datenbank zu senden. Der Wochenplan wird als strukturierte Seite mit allen Mahlzeiten und Zutaten erstellt.

## Voraussetzungen

1. Ein Notion-Account (kostenlos unter [notion.so](https://www.notion.so))
2. Eine Notion-Datenbank für die Wochenpläne

## Setup in 4 Schritten

### Schritt 1: Notion Integration erstellen

1. Öffnen Sie [https://www.notion.so/my-integrations](https://www.notion.so/my-integrations)
2. Klicken Sie auf **"+ Neue Integration"**
3. Geben Sie einen Namen ein (z.B. "Menüplaner")
4. Wählen Sie den Workspace aus
5. Klicken Sie auf **"Absenden"**
6. Kopieren Sie das **"Internal Integration Token"** (beginnt mit `secret_...`)

### Schritt 2: Notion Datenbank erstellen

1. Erstellen Sie eine neue Seite in Notion
2. Fügen Sie eine **Database (Tabelle)** hinzu
3. Benennen Sie die Datenbank (z.B. "Wochenpläne")
4. Erstellen Sie folgende Properties (Spalten):

| Property Name | Typ        | Beschreibung                    |
|---------------|------------|---------------------------------|
| **Name**      | Title      | Titel der Wochenplan-Seite     |
| **Woche**     | Number     | KW Nummer                       |
| **Jahr**      | Number     | Jahr                            |
| **Benutzer**  | Rich Text  | Name des Benutzers             |

> **Hinweis:** Die Property "Name" ist standardmäßig vorhanden. Sie müssen nur "Woche", "Jahr" und "Benutzer" hinzufügen.

### Schritt 3: Integration mit Datenbank verbinden

1. Öffnen Sie Ihre Wochenpläne-Datenbank
2. Klicken Sie oben rechts auf **"•••" (Mehr)**
3. Wählen Sie **"+ Verbindungen hinzufügen"** oder **"Add connections"**
4. Suchen Sie nach Ihrer Integration (z.B. "Menüplaner")
5. Klicken Sie darauf, um die Verbindung herzustellen

### Schritt 4: Database ID kopieren

1. Öffnen Sie Ihre Wochenpläne-Datenbank als **Vollseite**
2. Die URL hat folgendes Format:
   ```
   https://www.notion.so/[WORKSPACE]/[DATABASE_ID]?v=[VIEW_ID]
   ```
3. Kopieren Sie die **DATABASE_ID** (32 Zeichen zwischen dem letzten `/` und `?v=`)

   **Beispiel:**
   ```
   URL: https://www.notion.so/meinworkspace/abc123def456abc123def456abc123de?v=xyz
   DATABASE_ID: abc123def456abc123def456abc123de
   ```

### Schritt 5: Konfiguration in config.php

Öffnen Sie die Datei `config.php` und tragen Sie Ihre Credentials ein:

```php
// === NOTION API KONFIGURATION ===
if (!defined('NOTION_API_TOKEN')) {
    define('NOTION_API_TOKEN', 'secret_XXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
}
if (!defined('NOTION_DATABASE_ID')) {
    define('NOTION_DATABASE_ID', 'abc123def456abc123def456abc123de');
}
```

Ersetzen Sie:
- `secret_XXXXXXXXXXXXXXXXXXXXXXXXXXXXX` mit Ihrem Integration Token aus Schritt 1
- `abc123def456abc123def456abc123de` mit Ihrer Database ID aus Schritt 4

## Verwendung

1. Laden Sie einen Wochenplan im Menüplaner
2. Klicken Sie auf den **violetten "Notion"** Button
3. Bestätigen Sie den Export (falls Debug-Modus aktiviert ist)
4. Bei Erfolg öffnet sich optional die erstellte Notion-Seite

## Struktur der erstellten Notion-Seite

Die Seite wird automatisch mit folgender Struktur erstellt:

```
📄 Wochenplan KW 47/2025 - Max Mustermann

## Montag
### Mittagessen: Spaghetti Bolognese
Zutaten:
• 500g Hackfleisch
• 400g Spaghetti
• 1 Dose Tomaten
• ...

### Abendessen: Salat
Zutaten:
• Kopfsalat
• Gurke
• Tomaten
• ...

---

## Dienstag
...
```

## Troubleshooting

### Fehler: "Notion API Token ist nicht konfiguriert"
- Prüfen Sie, ob Sie das Token in `config.php` eingetragen haben
- Stellen Sie sicher, dass keine Leerzeichen vor/nach dem Token stehen

### Fehler: "Notion Database ID ist nicht konfiguriert"
- Prüfen Sie, ob Sie die Database ID korrekt kopiert haben
- Die ID sollte genau 32 Zeichen lang sein (ohne Bindestriche)

### Fehler: "Could not find database"
- Stellen Sie sicher, dass die Integration mit der Datenbank verbunden ist (Schritt 3)
- Prüfen Sie, ob die Database ID korrekt ist

### Fehler: "Unauthorized"
- Das Integration Token ist ungültig oder abgelaufen
- Erstellen Sie eine neue Integration und kopieren Sie das neue Token

## Erweiterte Konfiguration

In `config.php` können Sie auch folgende Einstellungen anpassen:

```php
// Notion API URL (normalerweise nicht ändern)
define('NOTION_API_URL', 'https://api.notion.com/v1');

// Notion API Version
define('NOTION_API_VERSION', '2022-06-28');

// Timeout für API-Aufrufe (in Sekunden)
define('NOTION_API_TIMEOUT', 30);

// Debug-Modus (schreibt Logs in logs/notion_api.log)
define('NOTION_DEBUG', false);
```

## Logs

Bei aktiviertem Debug-Modus (`NOTION_DEBUG = true`) werden alle API-Aufrufe in folgende Datei geloggt:
```
logs/notion_api.log
```

## Support

Bei Problemen:
1. Prüfen Sie die Browser-Konsole (F12) auf JavaScript-Fehler
2. Aktivieren Sie `NOTION_DEBUG` in der config.php
3. Prüfen Sie die Log-Datei `logs/notion_api.log`

## API-Dokumentation

Weitere Informationen zur Notion API:
- [Notion API Dokumentation](https://developers.notion.com/reference/intro)
- [Notion API Changelog](https://developers.notion.com/changelog)
