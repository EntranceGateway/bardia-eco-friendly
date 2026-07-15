ALTER TABLE `pages`
    ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL AFTER `content`,
    ADD COLUMN `meta_description` VARCHAR(500) DEFAULT NULL AFTER `meta_title`,
    ADD COLUMN `meta_image` VARCHAR(500) DEFAULT NULL AFTER `meta_description`,
    ADD COLUMN `canonical_url` VARCHAR(500) DEFAULT NULL AFTER `meta_image`,
    ADD COLUMN `robots` VARCHAR(50) NOT NULL DEFAULT 'index,follow' AFTER `canonical_url`;
