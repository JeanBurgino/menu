# 📅 Menüplaner v2.0

Ein moderner, familienfreundlicher Wochenplaner mit Bring! Integration.

## ✨ Features

- 📱 Responsive Design (Mobile & Desktop)
- 👥 Multi-User Support mit Profilen
- 📝 Rezeptverwaltung mit Zutaten
- 📅 Drag & Drop Wochenplanung
- 🔒 Mahlzeiten-Sperren
- 🎲 Zufallsgenerator für Rezepte
- 🛒 **Bring! Integration** (Direkte API & Deeplink)
- 🎨 Moderne UI mit Tailwind CSS

## 🚀 Installation

### Voraussetzungen

- PHP 7.4 oder höher
- MySQL 5.7 oder höher
- Apache mit mod_rewrite (empfohlen)
- cURL PHP Extension

### Schritt 1: Dateien hochladen

Lade alle Dateien auf deinen Webserver hoch.

### Schritt 2: Datenbank einrichten

```sql
-- Datenbank erstellen
CREATE DATABASE foodmenudb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Benutzer erstellen
CREATE USER 'food_db_user'@'localhost' IDENTIFIED BY 'dein_sicheres_passwort';
GRANT ALL PRIVILEGES ON foodmenudb.* TO 'food_db_user'@'localhost';
FLUSH PRIVILEGES;

-- Tabellen erstellen (siehe database_schema.sql)
```

### Schritt 3: Konfiguration

1. Kopiere `.env.example` zu `.env`:
   ```bash
   cp .env.example .env
   ```

2. Bearbeite `.env` und trage deine Datenbank-Zugangsdaten ein:
   ```
   DB_HOST=localhost
   DB_NAME=foodmenudb
   DB_USER=food_db_user
   DB_PASS=dein_passwort
   ```

3. Für Bring! Integration, bearbeite `bring-config.php`:
   ```php
   define('BRING_EMAIL', 'deine_email@example.com');
   define('BRING_PASSWORD', 'dein_bring_passwort');
   define('BRING_LIST_NAME', 'Deine Listename');
   define('BRING_USE_API', true);
   ```

### Schritt 4: Verzeichnisse erstellen

```bash
mkdir -p exports/bring logs
chmod 755 exports exports/bring logs
```

### Schritt 5: Fertig!

Öffne `index.php` im Browser und starte mit der Nutzung.

## 📁 Dateistruktur

```
/
├── index.php              # Hauptanwendung (Frontend)
├── api.php                # REST API
├── config.php             # Hauptkonfiguration
├── bring-config.php       # Bring! Konfiguration
├── .htaccess             # Apache Sicherheit
├── .env.example          # Environment Template
├── classes/              # PHP Klassen
│   ├── Database.php      # DB-Handler
│   └── BringAPI.php      # Bring! API Client
├── exports/              # Export-Dateien
│   └── bring/            # Bring! JSON Exports
└── logs/                 # Log-Dateien
    ├── php_errors.log
    ├── database.log
    ├── api_errors.log
    └── bring_api.log
```

## 🔧 Konfiguration

### Datenbank (config.php)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'food_db_user');
define('DB_PASS', 'dein_passwort');
define('DB_NAME', 'foodmenudb');
```

### Bring! API (bring-config.php)

```php
// Direkte API-Integration (EMPFOHLEN)
define('BRING_EMAIL', 'deine_email@example.com');
define('BRING_PASSWORD', 'dein_passwort');
define('BRING_LIST_NAME', 'OBI');
define('BRING_USE_API', true);
```

## 🛒 Bring! Integration

Der Menüplaner bietet zwei Methoden für die Bring! Integration:

### Methode 1: Direkte API (EMPFOHLEN)

- Artikel werden direkt zur Bring! Liste hinzugefügt
- Keine manuelle Interaktion nötig
- Erfordert Login-Daten in `bring-config.php`

**Vorteile:**
- ✅ Automatisch
- ✅ Zuverlässig
- ✅ Batch-Import

**Setup:**
1. `BRING_USE_API` auf `true` setzen
2. Email und Passwort eintragen
3. Listennamen konfigurieren

### Methode 2: Deeplink (Fallback)

- Öffnet Bring! App mit JSON-Import
- Manuelle Listenauswahl erforderlich
- Funktioniert ohne API-Zugriff

**Vorteile:**
- ✅ Keine Login-Daten nötig
- ✅ Funktioniert immer

## 🔐 Sicherheit

### Produktiv-Umgebung

1. **Environment auf Production setzen:**
   ```php
   // In config.php oder .env
   define('APP_ENV', 'production');
   ```

2. **Sensible Dateien schützen:**
   - `.htaccess` ist bereits konfiguriert
   - `config.php` und `bring-config.php` sind geschützt
   - Logs sind nicht öffentlich zugänglich

3. **HTTPS aktivieren:**
   - SSL-Zertifikat installieren (Let's Encrypt empfohlen)
   - Session-Cookie-Secure wird automatisch aktiviert

4. **Regelmäßige Backups:**
   ```bash
   # Datenbank Backup
   mysqldump -u food_db_user -p foodmenudb > backup_$(date +%F).sql
   ```

### Credentials Management

**NIEMALS** diese Dateien ins Git-Repository hochladen:
- `config.php` (wenn Credentials enthalten)
- `bring-config.php`
- `.env`
- `/logs/*`

Füge zur `.gitignore` hinzu:
```
config.php
bring-config.php
.env
/logs/
/exports/
```

## 📊 API Endpunkte

### Benutzer
- `GET /api.php?action=users` - Alle Benutzer
- `POST /api.php?action=users` - Benutzer erstellen
- `POST /api.php?action=update_user&id=X` - Benutzer bearbeiten
- `POST /api.php?action=delete_user&id=X` - Benutzer löschen

### Rezepte
- `GET /api.php?action=recipes` - Alle Rezepte
- `POST /api.php?action=recipes` - Rezept erstellen
- `POST /api.php?action=update_recipe&id=X` - Rezept bearbeiten
- `POST /api.php?action=delete_recipe&id=X` - Rezept löschen
- `GET /api.php?action=recipe_ingredients&id=X` - Zutaten laden
- `POST /api.php?action=recipe_ingredients&id=X` - Zutaten speichern

### Wochenplan
- `GET /api.php?action=weekplan&week=X&year=Y` - Wochenplan laden
- `POST /api.php?action=weekplan` - Wochenplan speichern
- `POST /api.php?action=update_meal` - Mahlzeit aktualisieren
- `POST /api.php?action=toggle_lock` - Sperre umschalten

### Bring! Export
- `POST /api.php?action=save_bring_recipe` - JSON für Deeplink
- `POST /api.php?action=export_to_bring_direct` - Direkter API-Export

## 🐛 Debugging

### Debug-Modus aktivieren

```php
// In config.php
$isProduction = false; // Zeigt Fehler an
define('BRING_DEBUG', true); // Aktiviert Bring! API Logging
```

### Logs prüfen

```bash
# PHP Fehler
tail -f logs/php_errors.log

# Datenbank Fehler
tail -f logs/database.log

# API Fehler
tail -f logs/api_errors.log

# Bring! API
tail -f logs/bring_api.log
```

### Bring! API testen

```bash
php debug-bring-api.php
```

## 🔄 Updates

### Von v1.0 auf v2.0

1. **Backup erstellen:**
   ```bash
   mysqldump -u food_db_user -p foodmenudb > backup_v1.sql
   cp -r . ../backup_v1/
   ```

2. **Neue Dateien hochladen:**
   - Behalte `config.php` und `bring-config.php`
   - Ersetze alle anderen Dateien

3. **Verzeichnisse erstellen:**
   ```bash
   mkdir -p classes exports/bring logs
   chmod 755 classes exports exports/bring logs
   ```

4. **Migration testen:**
   - In Browser öffnen
   - Prüfen ob Daten vorhanden sind

## 💡 Tipps & Tricks

### Performance

1. **PHP OpCache aktivieren:**
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

2. **MySQL Query Cache:**
   ```sql
   SET GLOBAL query_cache_size = 67108864;
   SET GLOBAL query_cache_type = 1;
   ```

### Workflow

1. **Wochenplan erstellen:**
   - Rezepte verwalten
   - Drag & Drop in Kalender
   - Wichtige Mahlzeiten sperren
   - Zufall-Button für Inspiration

2. **Einkaufsliste generieren:**
   - "Bring!" Button klicken
   - Artikel werden automatisch zur Liste hinzugefügt
   - In Bring! App öffnen und einkaufen

## 🆘 Support

Bei Problemen:

1. Logs prüfen (`/logs/`)
2. Debug-Modus aktivieren
3. Browser-Konsole öffnen (F12)
4. Bring! API mit `debug-bring-api.php` testen

## 📄 Lizenz

Privates Projekt - Alle Rechte vorbehalten.

## 👨‍💻 Entwickler

Entwickelt mit ❤️ für die Familie Bourquin.

---

**Version:** 2.0.0  
**Letzte Aktualisierung:** Oktober 2025
