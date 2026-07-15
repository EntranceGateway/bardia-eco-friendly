-- ============================================================
-- Bardiya Eco Friendly - Complete Database Schema
-- Homestay Backend API
-- 
-- Database Engine : MySQL 5.7+ / MariaDB 10.3+
-- Character Set   : utf8mb4 (full Unicode support)
-- Collation       : utf8mb4_unicode_ci
-- ============================================================

-- ============================================================
-- 1. CREATE DATABASE
-- ============================================================
CREATE DATABASE IF NOT EXISTS `bardiya_eco_friendly`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `bardiya_eco_friendly`;

-- ============================================================
-- 2. USERS TABLE (Admin Authentication / JWT Auth)
-- ============================================================
DROP TABLE IF EXISTS `homestays`;
DROP TABLE IF EXISTS `seo_redirects`;
DROP TABLE IF EXISTS `seo_schemas`;
DROP TABLE IF EXISTS `faqs`;
DROP TABLE IF EXISTS `seo_settings`;
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id`           INT           AUTO_INCREMENT PRIMARY KEY,
    `username`     VARCHAR(100)  NOT NULL,
    `email`        VARCHAR(255)  DEFAULT NULL,
    `password`     VARCHAR(255)  NOT NULL COMMENT 'Bcrypt hash via password_hash()',
    `reset_token`  VARCHAR(255)  DEFAULT NULL,
    `reset_expiry` DATETIME      DEFAULT NULL,
    `role`         VARCHAR(50)   NOT NULL DEFAULT 'admin',
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_users_username` (`username`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Admin users for JWT authentication';

-- ============================================================
-- 3. HOMESTAYS TABLE (Property Listings)
-- ============================================================
CREATE TABLE `homestays` (
    `id`              INT            AUTO_INCREMENT PRIMARY KEY,
    `name`            VARCHAR(255)   NOT NULL,
    `description`     TEXT           DEFAULT NULL,
    `location`        VARCHAR(255)   NOT NULL,
    `price_per_night` DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `max_guests`      INT            NOT NULL DEFAULT 1,
    `image_url`       VARCHAR(500)   DEFAULT NULL,
    `is_available`    TINYINT(1)     NOT NULL DEFAULT 1 COMMENT '1=available, 0=unavailable',
    `created_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_homestays_location` (`location`),
    INDEX `idx_homestays_available` (`is_available`),
    INDEX `idx_homestays_price` (`price_per_night`),
    INDEX `idx_homestays_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Eco-friendly homestay property listings';

-- ============================================================
-- 4. PAGES TABLE (CMS Content)
-- ============================================================
CREATE TABLE `pages` (
    `id`         INT            AUTO_INCREMENT PRIMARY KEY,
    `title`      VARCHAR(255)   NOT NULL,
    `slug`       VARCHAR(255)   NOT NULL,
    `content`    LONGTEXT       DEFAULT NULL,
    `meta_title` VARCHAR(255)   DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `meta_image` VARCHAR(500)   DEFAULT NULL,
    `canonical_url` VARCHAR(500) DEFAULT NULL,
    `robots`     VARCHAR(50)    NOT NULL DEFAULT 'index,follow',
    `language_code` VARCHAR(10) NOT NULL DEFAULT 'en',
    `translation_group` VARCHAR(100) DEFAULT NULL,
    `hreflang_url` VARCHAR(500) DEFAULT NULL,
    `status`     VARCHAR(50)    NOT NULL DEFAULT 'draft' COMMENT 'draft|published|archived',
    `created_at` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_pages_slug` (`slug`),
    UNIQUE KEY `uk_pages_lang_group` (`translation_group`, `language_code`),
    INDEX `idx_pages_status` (`status`),
    INDEX `idx_pages_language` (`language_code`),
    INDEX `idx_pages_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='CMS content pages';

-- ============================================================
-- 5. DEFAULT ADMIN USER
-- ============================================================
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$s0UIe3lcvkkNMnFSGtNiXehLa98Db3lYQxjtsnxENLu5xMCuRxilq', 'admin');

-- ============================================================
-- 6. SAMPLE SEED DATA
-- ============================================================
INSERT INTO `homestays` (`name`, `description`, `location`, `price_per_night`, `max_guests`, `image_url`, `is_available`) VALUES
('Bardiya Jungle Lodge', 'An eco-friendly lodge nestled at the edge of Bardiya National Park.', 'Thakurdwara, Bardiya', 2500.00, 4, NULL, 1),
('Tharu Heritage Homestay', 'Experience authentic Tharu culture in a traditional mud-walled home.', 'Gulariya, Bardiya', 1500.00, 3, NULL, 1);

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `meta_image`, `canonical_url`, `robots`, `status`) VALUES
('About Us', 'about-us', '<h2>Welcome to Bardiya Eco Friendly</h2>', NULL, NULL, NULL, NULL, 'index,follow', 'published'),
('Contact Us', 'contact-us', '<h2>Get in Touch</h2>', 'Contact Bardia Eco-Friendly Homestay', 'Contact Bardia Eco-Friendly Homestay to plan your stay, ask about safari packages, or request travel information.', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/contact', 'index,follow', 'published');

-- ============================================================
-- 7. PACKAGE CATEGORIES TABLE
-- ============================================================
DROP TABLE IF EXISTS `package_features`;
DROP TABLE IF EXISTS `packages`;
DROP TABLE IF EXISTS `package_categories`;

CREATE TABLE `package_categories` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100)   NOT NULL,
    `slug`          VARCHAR(100)   NOT NULL,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_pkgcat_slug` (`slug`),
    INDEX `idx_pkgcat_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. PACKAGES TABLE
-- ============================================================
CREATE TABLE `packages` (
    `id`            INT            AUTO_INCREMENT PRIMARY KEY,
    `category_id`   BIGINT         NOT NULL,
    `icon`          VARCHAR(10)    DEFAULT NULL,
    `name`          VARCHAR(100)   NOT NULL,
    `duration`      VARCHAR(50)    DEFAULT NULL,
    `price`         DECIMAL(10,2)  NOT NULL,
    `currency`      VARCHAR(5)     NOT NULL DEFAULT '₹',
    `price_note`    VARCHAR(50)    DEFAULT NULL,
    `description`   TEXT           NOT NULL,
    `is_featured`   TINYINT(1)     NOT NULL DEFAULT 0,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_packages_category` FOREIGN KEY (`category_id`) REFERENCES `package_categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_packages_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. PACKAGE FEATURES TABLE
-- ============================================================
CREATE TABLE `package_features` (
    `id`            INT            AUTO_INCREMENT PRIMARY KEY,
    `package_id`    INT            NOT NULL,
    `feature_text`  VARCHAR(255)   NOT NULL,
    `display_order` INT            NOT NULL DEFAULT 0,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_pkgfeat_package` FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `package_categories` (`name`, `slug`, `display_order`, `is_active`) VALUES
('Homestay', 'homestay', 1, 1),
('Safari',   'safari',   2, 1);

-- ============================================================
-- 10. GALLERY CATEGORIES TABLE
-- ============================================================
DROP TABLE IF EXISTS `gallery_images`;
DROP TABLE IF EXISTS `gallery_categories`;

CREATE TABLE `gallery_categories` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100)   NOT NULL,
    `slug`          VARCHAR(100)   NOT NULL,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_galcat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. GALLERY IMAGES TABLE
-- ============================================================
CREATE TABLE `gallery_images` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `category_id`   BIGINT         NOT NULL,
    `image_url`     VARCHAR(500)   NOT NULL,
    `alt_text`      VARCHAR(255)   DEFAULT NULL,
    `title_text`    VARCHAR(255)   DEFAULT NULL,
    `caption`       VARCHAR(500)   DEFAULT NULL,
    `width`         INT UNSIGNED   DEFAULT NULL,
    `height`        INT UNSIGNED   DEFAULT NULL,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_galimg_category` FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gallery_categories` (`name`, `slug`, `display_order`, `is_active`) VALUES
('Gallery',  'gallery',  1, 1),
('Wildlife', 'wildlife', 2, 1);

-- ============================================================
-- 12. SEO SETTINGS / REDIRECTS / FAQ / SCHEMA
-- ============================================================
CREATE TABLE `seo_settings` (
    `setting_key`   VARCHAR(100)   NOT NULL PRIMARY KEY,
    `setting_value` LONGTEXT       DEFAULT NULL,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `source_path`   VARCHAR(500)   NOT NULL,
    `target_url`    VARCHAR(500)   NOT NULL,
    `status_code`   SMALLINT       NOT NULL DEFAULT 301,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_redirect_source` (`source_path`),
    INDEX `idx_redirect_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faqs` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `page_slug`     VARCHAR(255)   NOT NULL,
    `question`      VARCHAR(500)   NOT NULL,
    `answer`        TEXT           NOT NULL,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_faq_page` (`page_slug`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seo_schemas` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `page_slug`     VARCHAR(255)   NOT NULL,
    `schema_type`   VARCHAR(100)   NOT NULL,
    `schema_json`   LONGTEXT       NOT NULL,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_schema_page` (`page_slug`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. CONTACT METHODS TABLE
-- ============================================================
DROP TABLE IF EXISTS `email_history`;
DROP TABLE IF EXISTS `contact_submissions`;
DROP TABLE IF EXISTS `contact_methods`;
DROP TABLE IF EXISTS `social_links`;
DROP TABLE IF EXISTS `email_templates`;

CREATE TABLE `contact_methods` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `icon`          VARCHAR(10)    NOT NULL,
    `title`         VARCHAR(100)   NOT NULL,
    `detail`        VARCHAR(255)   NOT NULL,
    `description`   TEXT           DEFAULT NULL,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. CONTACT SUBMISSIONS TABLE
-- ============================================================
CREATE TABLE `contact_submissions` (
    `id`                BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `full_name`         VARCHAR(255)   NOT NULL,
    `email`             VARCHAR(255)   NOT NULL,
    `phone`             VARCHAR(20)    NOT NULL,
    `num_guests`        VARCHAR(20)    NOT NULL,
    `preferred_package` VARCHAR(100)   DEFAULT NULL,
    `travel_dates`      VARCHAR(255)   DEFAULT NULL,
    `message`           TEXT           NOT NULL,
    `status`            ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
    `email_count`       INT            NOT NULL DEFAULT 0,
    `last_email_sent_at` DATETIME      DEFAULT NULL,
    `created_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_contact_sub_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. EMAIL HISTORY TABLE
-- ============================================================
CREATE TABLE `email_history` (
    `id`                BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `submission_id`     BIGINT         NOT NULL,
    `recipient_email`   VARCHAR(255)   NOT NULL,
    `recipient_name`    VARCHAR(255)   NOT NULL,
    `subject`           VARCHAR(500)   NOT NULL,
    `body_html`         LONGTEXT       NOT NULL,
    `body_plain`        LONGTEXT       DEFAULT NULL,
    `sent_by_user_id`   INT            NOT NULL,
    `sent_at`           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`            ENUM('sent','failed','pending') NOT NULL DEFAULT 'sent',
    `error_message`     TEXT           DEFAULT NULL,
    
    CONSTRAINT `fk_email_submission` FOREIGN KEY (`submission_id`) REFERENCES `contact_submissions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_email_user` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. EMAIL TEMPLATES TABLE
-- ============================================================
CREATE TABLE `email_templates` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100)   NOT NULL,
    `subject`       VARCHAR(500)   NOT NULL,
    `body_html`     LONGTEXT       NOT NULL,
    `description`   TEXT           DEFAULT NULL,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_template_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `email_templates` (`name`, `subject`, `body_html`, `description`, `is_active`) VALUES
('general_enquiry_response', 'Re: Your Message - Bardiya Eco Friendly',
'<p>Dear {{guest_name}},</p><p>Thank you for contacting Bardiya Eco Friendly.</p><p>We appreciate your interest in our eco-friendly homestays and safari experiences in beautiful Bardiya, Nepal.</p><p>We have received your message and will respond with detailed information shortly.</p><p>Looking forward to hosting you in Bardiya!</p><p>Warm regards,<br>{{admin_name}}<br>{{company_name}}</p>',
'General response for enquiries', 1);

-- ============================================================
-- 16. SOCIAL LINKS TABLE
-- ============================================================
CREATE TABLE `social_links` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `icon_name`     VARCHAR(100)   NOT NULL,
    `label`         VARCHAR(50)    NOT NULL,
    `href`          VARCHAR(500)   NOT NULL,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
