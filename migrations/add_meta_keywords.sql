-- ============================================================
-- Migration: Add meta_keywords to pages and blog_posts
-- Run: mysql -u root bardiya_eco_friendly < migrations/add_meta_keywords.sql
-- ============================================================

-- Add meta_keywords to pages table
ALTER TABLE `pages` ADD COLUMN `meta_keywords` VARCHAR(500) DEFAULT NULL AFTER `meta_description`;

-- Add meta_keywords to blog_posts table
ALTER TABLE `blog_posts` ADD COLUMN `meta_keywords` VARCHAR(500) DEFAULT NULL AFTER `meta_description`;

-- Add default_keywords to global SEO settings
INSERT INTO `seo_settings` (`setting_key`, `setting_value`) VALUES
('default_keywords', 'Bardia, eco-friendly, homestay, Nepal, safari, wildlife, Bardiya National Park')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
