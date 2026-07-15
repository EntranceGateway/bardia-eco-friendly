ALTER TABLE `gallery_images`
    ADD COLUMN `title_text` VARCHAR(255) DEFAULT NULL AFTER `alt_text`,
    ADD COLUMN `caption` VARCHAR(500) DEFAULT NULL AFTER `title_text`;

CREATE TABLE `seo_settings` (
    `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` LONGTEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `seo_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Bardia Eco-Friendly Homestay'),
('default_title', 'Bardia Eco-Friendly Homestay'),
('default_description', 'Stay near Bardia National Park at an eco-friendly homestay offering wildlife safaris, local food, and responsible travel experiences.'),
('default_image', '/logo.png'),
('robots_txt', 'User-agent: *\nAllow: /\n\nSitemap: https://www.bardiaecofriendlyhomestay.com/sitemap.xml'),
('google_reviews_enabled', '1'),
('google_reviews_provider', 'elfsight'),
('aggregate_rating_value', '5.0'),
('aggregate_rating_count', '120');

CREATE TABLE `seo_redirects` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `source_path` VARCHAR(500) NOT NULL,
    `target_url` VARCHAR(500) NOT NULL,
    `status_code` SMALLINT NOT NULL DEFAULT 301,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_redirect_source` (`source_path`),
    INDEX `idx_redirect_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faqs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `page_slug` VARCHAR(255) NOT NULL,
    `question` VARCHAR(500) NOT NULL,
    `answer` TEXT NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_faq_page` (`page_slug`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seo_schemas` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `page_slug` VARCHAR(255) NOT NULL,
    `schema_type` VARCHAR(100) NOT NULL,
    `schema_json` LONGTEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_schema_page` (`page_slug`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
