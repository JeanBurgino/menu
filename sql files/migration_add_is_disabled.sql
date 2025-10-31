-- ============================================
-- Migration: Add is_disabled field to week_plan
-- Date: 2025-10-31
-- Description: Fügt ein is_disabled Feld hinzu, um Tage zu markieren,
--              an denen nichts eingeplant werden soll
-- ============================================

USE foodmenudb;

-- Füge is_disabled Feld zur week_plan Tabelle hinzu
ALTER TABLE `week_plan`
ADD COLUMN `is_disabled` tinyint(1) DEFAULT 0 AFTER `is_locked`;

-- Aktualisiere View
CREATE OR REPLACE VIEW `v_current_week_plan` AS
SELECT
    wp.id,
    wp.week_number,
    wp.year,
    wp.weekday,
    wp.meal_type,
    wp.is_locked,
    wp.is_disabled,
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

-- Update Schema-Version
INSERT INTO schema_version (version) VALUES ('2.1.0')
ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP;

SELECT '✅ Migration erfolgreich: is_disabled Feld hinzugefügt!' as Status;
