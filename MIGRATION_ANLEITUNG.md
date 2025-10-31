# Migrations-Anleitung: Deaktivierungs-Funktion

## Übersicht
Diese Migration fügt die Möglichkeit hinzu, einzelne Tage/Mahlzeiten in der Wochenübersicht zu deaktivieren.

## Was wurde hinzugefügt?

### Neue Features:
1. **Kreuz-Icon** neben dem Schloss-Symbol
2. **Deaktivierungs-Funktion**: Wenn das Kreuz gedrückt wird:
   - Wird das aktuelle Menü entfernt
   - Wird verhindert, dass neue Menüs ausgewählt werden können
   - Wird der Bereich rötlich gekennzeichnet
3. **Reaktivierung**: Erneutes Drücken des Kreuzes aktiviert den Eintrag wieder

## Datenbank-Migration durchführen

### Option 1: Über die Kommandozeile
```bash
mysql -u food_db_user -pfrPrY7XUtayS4g0Agwdw foodmenudb < "sql files/migration_add_is_disabled.sql"
```

### Option 2: Über phpMyAdmin
1. Öffne phpMyAdmin
2. Wähle die Datenbank `foodmenudb`
3. Klicke auf "SQL"
4. Kopiere den Inhalt von `sql files/migration_add_is_disabled.sql`
5. Führe das SQL aus

### Option 3: Manuell
Führe folgende SQL-Befehle aus:

```sql
USE foodmenudb;

-- Füge is_disabled Feld zur week_plan Tabelle hinzu
ALTER TABLE `week_plan`
ADD COLUMN `is_disabled` tinyint(1) DEFAULT 0 AFTER `is_locked`;

-- Aktualisiere Schema-Version
INSERT INTO schema_version (version) VALUES ('2.1.0')
ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP;
```

## Änderungen im Detail

### Datenbank:
- Neues Feld `is_disabled` in Tabelle `week_plan`

### Backend (api.php):
- Neue Funktion `toggleDisabled()`
- Erweiterte `getWeekPlan()` Funktion um `is_disabled` Status

### Frontend (index.php):
- Neue globale Variable `disabledMeals`
- Kreuz-Icon neben Schloss-Icon
- Rötliche Kennzeichnung für deaktivierte Einträge
- Verhinderte Selektion bei deaktivierten Einträgen
- Angepasste `randomizeWeekPlan()` Funktion (überspringt disabled-Einträge)

## Nutzung

1. Klicke auf das **Kreuz-Icon** neben dem Schloss
2. Der Eintrag wird **rötlich** markiert mit "🚫 Nicht verfügbar"
3. Es können **keine neuen Menüs** ausgewählt werden
4. Klicke erneut auf das Kreuz, um den Eintrag **wieder zu aktivieren**

## Troubleshooting

### Migration schlägt fehl
- Prüfe, ob die Spalte `is_disabled` bereits existiert
- Prüfe Datenbankverbindung und Berechtigungen

### Frontend zeigt Kreuz nicht an
- Lösche Browser-Cache
- Prüfe Browser-Konsole auf Fehler
- Stelle sicher, dass die Migration erfolgreich war

## Rückgängig machen

Falls du die Änderungen rückgängig machen möchtest:

```sql
USE foodmenudb;

-- Entferne is_disabled Spalte
ALTER TABLE `week_plan` DROP COLUMN `is_disabled`;

-- Setze Schema-Version zurück
DELETE FROM schema_version WHERE version = '2.1.0';
```

## Support

Bei Problemen kontaktiere den Entwickler oder erstelle ein Issue im Repository.
