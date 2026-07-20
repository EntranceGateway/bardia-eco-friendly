-- ============================================================
-- Migration: Add SEO fields to packages and gallery
-- Run: mysql -u root bardiya_eco_friendly < migrations/add_seo_to_packages_gallery.sql
-- ============================================================

-- 1. package_categories
ALTER TABLE `package_categories`
    ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `meta_description` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `meta_keywords` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `meta_image` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `canonical_url` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `robots` VARCHAR(50) NOT NULL DEFAULT 'index,follow';

-- 2. packages
ALTER TABLE `packages`
    ADD COLUMN `slug` VARCHAR(100) DEFAULT NULL AFTER `name`,
    ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `meta_description` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `meta_keywords` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `meta_image` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `canonical_url` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `robots` VARCHAR(50) NOT NULL DEFAULT 'index,follow';

-- Generate slugs for existing packages
UPDATE `packages` SET `slug` = LOWER(REPLACE(REPLACE(REPLACE(`name`, ' ', '-'), '&', 'and'), ',', '')) WHERE `slug` IS NULL;
ALTER TABLE `packages` MODIFY COLUMN `slug` VARCHAR(100) NOT NULL;
ALTER TABLE `packages` ADD UNIQUE KEY `uk_packages_slug` (`slug`);

-- 3. gallery_categories
ALTER TABLE `gallery_categories`
    ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `meta_description` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `meta_keywords` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `meta_image` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `canonical_url` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `robots` VARCHAR(50) NOT NULL DEFAULT 'index,follow';

-- 4. gallery_images
ALTER TABLE `gallery_images`
    ADD COLUMN `slug` VARCHAR(100) DEFAULT NULL AFTER `image_url`,
    ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `meta_description` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `meta_keywords` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `meta_image` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `canonical_url` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN `robots` VARCHAR(50) NOT NULL DEFAULT 'index,follow';

-- Generate unique slugs for existing gallery images based on ID to avoid empty slugs
UPDATE `gallery_images` SET `slug` = CONCAT('image-', `id`) WHERE `slug` IS NULL;
ALTER TABLE `gallery_images` ADD UNIQUE KEY `uk_galimg_slug` (`slug`);
