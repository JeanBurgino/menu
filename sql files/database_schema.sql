-- ============================================
-- Menüplaner Datenbank Schema
-- Version: 2.0
-- ============================================

-- Datenbank erstellen (falls nicht vorhanden)
CREATE DATABASE IF NOT EXISTS foodmenudb 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE foodmenudb;

-- ============================================
-- Tabelle: users
-- ============================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `profile_image` varchar(10) DEFAULT '👤',
  `profile_picture` TEXT DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Benutzer einfügen
INSERT IGNORE INTO `users` (`id`, `name`, `profile_image`, `is_admin`) VALUES
(1, 'Mama', '👩', 0),
(2, 'Papa', '👨', 1),
(3, 'Kind1', '👦', 0),
(4, 'Kind2', '👧', 0);

-- ============================================
-- Tabelle: recipes
-- ============================================

CREATE TABLE IF NOT EXISTS `recipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `ingredients` text DEFAULT NULL,
  `is_lunch` tinyint(1) DEFAULT 1,
  `is_dinner` tinyint(1) DEFAULT 1,
  `is_weekend` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_modified_by` int(11) DEFAULT NULL,
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_title` (`title`),
  KEY `idx_lunch` (`is_lunch`),
  KEY `idx_dinner` (`is_dinner`),
  KEY `idx_weekend` (`is_weekend`),
  KEY `fk_recipes_created_by` (`created_by`),
  KEY `fk_recipes_modified_by` (`last_modified_by`),
  CONSTRAINT `fk_recipes_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_recipes_modified_by` FOREIGN KEY (`last_modified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabelle: recipe_ingredients
-- ============================================

CREATE TABLE IF NOT EXISTS `recipe_ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `specification` varchar(200) DEFAULT NULL,
  `position` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recipe_id` (`recipe_id`),
  KEY `idx_position` (`position`),
  CONSTRAINT `fk_ingredients_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabelle: week_plan
-- ============================================

CREATE TABLE IF NOT EXISTS `week_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `week_number` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `weekday` varchar(20) NOT NULL,
  `meal_type` varchar(20) NOT NULL,
  `recipe_id` int(11) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `last_modified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_meal` (`week_number`,`year`,`weekday`,`meal_type`),
  KEY `idx_week_year` (`week_number`,`year`),
  KEY `idx_weekday` (`weekday`),
  KEY `idx_meal_type` (`meal_type`),
  KEY `fk_weekplan_recipe` (`recipe_id`),
  KEY `fk_weekplan_user` (`last_modified_by`),
  CONSTRAINT `fk_weekplan_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_weekplan_user` FOREIGN KEY (`last_modified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Views für einfachere Abfragen
-- ============================================

-- View: Aktueller Wochenplan mit allen Details
CREATE OR REPLACE VIEW `v_current_week_plan` AS
SELECT 
    wp.id,
    wp.week_number,
    wp.year,
    wp.weekday,
    wp.meal_type,
    wp.is_locked,
    r.id as recipe_id,
    r.title as recipe_title,
    u.name as modified_by_name,
    u.profile_image as modified_by_image,
    wp.updated_at
FROM week_plan wp
LEFT JOIN recipes r ON wp.recipe_id = r.id
LEFT JOIN users u ON wp.last_modified_by = u.id
WHERE wp.week_number = WEEK(CURDATE(), 3)
  AND wp.year = YEAR(CURDATE());

-- View: Rezepte mit Zutaten-Count
CREATE OR REPLACE VIEW `v_recipes_with_stats` AS
SELECT 
    r.*,
    COUNT(DISTINCT ri.id) as ingredient_count,
    u1.name as creator_name,
    u2.name as modifier_name
FROM recipes r
LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
LEFT JOIN users u1 ON r.created_by = u1.id
LEFT JOIN users u2 ON r.last_modified_by = u2.id
GROUP BY r.id;

-- ============================================
-- Stored Procedures (optional)
-- ============================================

DELIMITER //

-- Procedure: Wochenplan kopieren
CREATE PROCEDURE IF NOT EXISTS `sp_copy_week_plan`(
    IN source_week INT,
    IN source_year INT,
    IN target_week INT,
    IN target_year INT,
    IN user_id INT
)
BEGIN
    -- Lösche existierenden Zielplan
    DELETE FROM week_plan 
    WHERE week_number = target_week 
      AND year = target_year;
    
    -- Kopiere Quellplan
    INSERT INTO week_plan (week_number, year, weekday, meal_type, recipe_id, is_locked, last_modified_by)
    SELECT target_week, target_year, weekday, meal_type, recipe_id, 0, user_id
    FROM week_plan
    WHERE week_number = source_week 
      AND year = source_year;
END //

-- Procedure: Statistiken für Rezept
CREATE PROCEDURE IF NOT EXISTS `sp_recipe_stats`(
    IN recipe_id INT
)
BEGIN
    SELECT 
        r.title,
        COUNT(DISTINCT wp.id) as times_used,
        COUNT(DISTINCT ri.id) as ingredient_count,
        MIN(wp.created_at) as first_used,
        MAX(wp.updated_at) as last_used
    FROM recipes r
    LEFT JOIN week_plan wp ON r.id = wp.recipe_id
    LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
    WHERE r.id = recipe_id
    GROUP BY r.id;
END //

DELIMITER ;

-- ============================================
-- Trigger für Audit-Trail (optional)
-- ============================================

-- Trigger: Log bei Rezept-Änderungen
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `old_data` json DEFAULT NULL,
  `new_data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Indexes für Performance
-- ============================================

-- Optimierung für häufige Abfragen
ALTER TABLE recipes 
  ADD FULLTEXT KEY `ft_title` (`title`);

-- ============================================
-- Berechtigungen setzen
-- ============================================

-- Erstelle Benutzer (falls nicht vorhanden)
CREATE USER IF NOT EXISTS 'food_db_user'@'localhost' IDENTIFIED BY 'frPrY7XUtayS4g0Agwdw';

-- Gebe Berechtigungen
GRANT SELECT, INSERT, UPDATE, DELETE ON foodmenudb.* TO 'food_db_user'@'localhost';
FLUSH PRIVILEGES;

-- ============================================
-- Testdaten (optional)
-- ============================================

-- Beispiel-Rezepte
INSERT IGNORE INTO recipes (id, title, is_lunch, is_dinner, is_weekend, created_by) VALUES
(1, 'Spaghetti Bolognese', 1, 1, 0, 2),
(2, 'Pizza Margherita', 1, 1, 0, 2),
(3, 'Lasagne', 1, 1, 0, 2),
(4, 'Schnitzel mit Pommes', 1, 1, 0, 2),
(5, 'Gemüsecurry', 1, 1, 0, 1);

-- Beispiel-Zutaten für Spaghetti Bolognese
INSERT IGNORE INTO recipe_ingredients (recipe_id, name, specification, position) VALUES
(1, 'Spaghetti', '500g', 0),
(1, 'Hackfleisch', '500g', 1),
(1, 'Tomatensoße', '400ml', 2),
(1, 'Zwiebeln', '2 Stück', 3),
(1, 'Knoblauch', '2 Zehen', 4),
(1, 'Parmesan', 'nach Geschmack', 5);

-- ============================================
-- Wartung & Cleanup
-- ============================================

-- Alte Wochenpläne löschen (älter als 1 Jahr)
-- DELETE FROM week_plan WHERE year < YEAR(CURDATE()) - 1;

-- Orphan Ingredients bereinigen
-- DELETE FROM recipe_ingredients WHERE recipe_id NOT IN (SELECT id FROM recipes);

-- ============================================
-- Backup Empfehlung
-- ============================================

/*
Erstelle regelmäßig Backups:

mysqldump -u food_db_user -p foodmenudb > backup_$(date +%F).sql

Oder mit komprimierung:
mysqldump -u food_db_user -p foodmenudb | gzip > backup_$(date +%F).sql.gz

Restore:
mysql -u food_db_user -p foodmenudb < backup_YYYY-MM-DD.sql
*/

-- ============================================
-- Schema-Version
-- ============================================

CREATE TABLE IF NOT EXISTS `schema_version` (
  `version` varchar(20) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_version (version) VALUES ('2.0.0');

-- Ende des Schemas
SELECT '✅ Datenbank-Schema erfolgreich erstellt/aktualisiert!' as Status;
