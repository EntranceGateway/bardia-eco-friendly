ALTER TABLE `pages`
    ADD COLUMN `language_code` VARCHAR(10) NOT NULL DEFAULT 'en' AFTER `robots`,
    ADD COLUMN `translation_group` VARCHAR(100) DEFAULT NULL AFTER `language_code`,
    ADD COLUMN `hreflang_url` VARCHAR(500) DEFAULT NULL AFTER `translation_group`,
    ADD UNIQUE KEY `uk_pages_lang_group` (`translation_group`, `language_code`),
    ADD INDEX `idx_pages_language` (`language_code`);
