-- ============================================
-- Migration: Erweitere profile_picture Spalte
-- ============================================
--
-- Problem: VARCHAR(255) ist zu klein für Base64-kodierte Bilder
-- Lösung: Ändern auf TEXT
--
-- Aufruf über phpMyAdmin oder MySQL CLI:
-- mysql -u food_db_user -p foodmenudb < migration_profile_picture.sql
--

ALTER TABLE `users`
MODIFY COLUMN `profile_picture` TEXT DEFAULT NULL;

-- Verifizierung:
-- SHOW COLUMNS FROM users LIKE 'profile_picture';
