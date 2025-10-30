# 🚀 Quick-Start Guide - Menüplaner v2.0

**Installations-Zeit: ~15 Minuten**

---

## ⚡ In 5 Schritten zur fertigen App

### 1️⃣ Dateien hochladen (2 Min)

Lade alle Dateien auf deinen Webserver hoch:

```bash
# Via FTP oder SSH
scp -r * user@server:/var/www/html/menueplaner/
```

---

### 2️⃣ Datenbank einrichten (5 Min)

**Option A - Über phpMyAdmin:**
1. Öffne phpMyAdmin
2. Importiere `database_schema.sql`
3. Fertig!

**Option B - Über SSH:**
```bash
mysql -u root -p < database_schema.sql
```

**Credentials anpassen:**
```sql
-- In database_schema.sql, Zeile ~350:
CREATE USER 'food_db_user'@'localhost' IDENTIFIED BY 'DEIN_SICHERES_PASSWORT';
```

---

### 3️⃣ Config anpassen (3 Min)

**config.php:**
```php
// Zeile 11-14
define('DB_HOST', 'localhost');
define('DB_USER', 'food_db_user');
define('DB_PASS', 'dein_passwort_hier');  // ← ANPASSEN!
define('DB_NAME', 'foodmenudb');
```

**bring-config.php:**
```php
// Zeile 10-11
define('BRING_EMAIL', 'deine@email.com');      // ← ANPASSEN!
define('BRING_PASSWORD', 'dein_passwort');     // ← ANPASSEN!
define('BRING_LIST_NAME', 'Dein Listenname');  // ← ANPASSEN!
```

---

### 4️⃣ Verzeichnisse erstellen (1 Min)

```bash
mkdir -p classes exports/bring logs
chmod 755 classes exports exports/bring logs
```

**Oder via FTP:**
- Erstelle Ordner: `classes`, `exports`, `exports/bring`, `logs`
- Setze Rechte auf 755

---

### 5️⃣ Testen (4 Min)

**Terminal:**
```bash
php debug-bring-api.php
```

**Browser:**
1. Öffne: `http://deine-domain.com/menueplaner/`
2. Wähle Benutzer aus
3. Fertig! 🎉

---

## ⚠️ Troubleshooting

### Problem: "Datenbankverbindung fehlgeschlagen"

**Lösung:**
```bash
# Prüfe Credentials in config.php
# Teste DB-Zugriff:
mysql -u food_db_user -p foodmenudb
```

### Problem: "BringAPI.php nicht gefunden"

**Lösung:**
```bash
# Prüfe ob Ordner existiert:
ls -la classes/
# Sollte zeigen: BringAPI.php, Database.php
```

### Problem: "Bring! Login fehlgeschlagen"

**Lösung:**
```bash
# Teste mit Debug-Tool:
php debug-bring-api.php

# Prüfe bring-config.php:
# - Email korrekt?
# - Passwort korrekt?
# - BRING_USE_API = true?
```

### Problem: "Permission denied" bei Logs

**Lösung:**
```bash
chmod 755 logs
chmod 666 logs/*.log
```

---

## 🎯 Next Steps nach Installation

### Sofort:
1. ✅ Ersten Benutzer anlegen
2. ✅ Erstes Rezept hinzufügen
3. ✅ Wochenplan testen
4. ✅ Bring! Export testen

### Security (WICHTIG!):
1. ⚠️ Ändere Default-Passwörter
2. ⚠️ Aktiviere .htaccess
3. ⚠️ Production-Mode aktivieren:
   ```php
   // In config.php:
   $isProduction = true;
   ```

### Optional:
1. ✅ SSL-Zertifikat (Let's Encrypt)
2. ✅ .env für Credentials
3. ✅ Automatische Backups

---

## 📱 Mobile-Tipp

**Zur Startseite hinzufügen:**

**iOS (Safari):**
1. Öffne Menüplaner
2. Teilen-Symbol → "Zum Home-Bildschirm"
3. App-Icon erscheint

**Android (Chrome):**
1. Öffne Menüplaner
2. Menü → "Zum Startbildschirm hinzufügen"
3. App-Icon erscheint

---

## 🔧 Production Checklist

Vor dem Live-Gehen:

- [ ] Datenbank-Backup erstellt
- [ ] Credentials geändert
- [ ] .htaccess aktiv
- [ ] Production-Mode aktiviert
- [ ] SSL-Zertifikat installiert
- [ ] Logs überwacht
- [ ] Bring! Integration getestet
- [ ] Mobile-Ansicht getestet

---

## 📞 Schnelle Hilfe

**Logs prüfen:**
```bash
tail -f logs/api_errors.log
```

**Debug-Modus:**
```php
// In config.php:
$isProduction = false;  // Zeigt Fehler
define('BRING_DEBUG', true);  // Detaillierte Logs
```

**Reset:**
```bash
# Wenn etwas schief geht:
mysql -u food_db_user -p foodmenudb < backup.sql
```

---

## 🎉 Du bist fertig!

Die App läuft jetzt und ist bereit für den Einsatz.

**Nächste Schritte:**
1. Lies [README.md](README.md) für Details
2. Schau dir [CHANGES.md](CHANGES.md) für Features an
3. Starte mit deinem ersten Wochenplan!

---

**Viel Spaß mit dem Menüplaner! 🍽️**
