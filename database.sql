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
    `meta_keywords` VARCHAR(500) DEFAULT NULL,
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

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `meta_keywords`, `meta_image`, `canonical_url`, `robots`, `status`) VALUES
('Home', 'home', '<h2>Welcome to Bardia Eco-Friendly Homestay</h2>', 'Bardia Eco-Friendly Homestay | Jungle Safaris & Wildlife Stay', 'Experience authentic eco-friendly homestay and guided jungle safaris near Bardia National Park in Nepal.', 'Bardia, eco-friendly homestay, safari, Nepal, wildlife, Royal Bengal Tiger', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/', 'index,follow', 'published'),
('About Us', 'about-us', '<h2>Welcome to Bardiya Eco Friendly</h2>', 'About Us | Bardia Eco-Friendly Homestay', 'Learn about our eco-friendly homestay, community conservation values, and local Tharu heritage in Bardia.', 'About Bardia Homestay, eco tourism Nepal, Tharu culture', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/about-us', 'index,follow', 'published'),
('Contact Us', 'contact-us', '<h2>Get in Touch</h2>', 'Contact Bardia Eco-Friendly Homestay', 'Contact Bardia Eco-Friendly Homestay to plan your stay, ask about safari packages, or request travel information.', 'contact Bardia, homestay booking, safari inquiry', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/contact-us', 'index,follow', 'published'),
('Contact', 'contact', '<h2>Contact Us</h2>', 'Contact Bardia Eco-Friendly Homestay', 'Contact Bardia Eco-Friendly Homestay to plan your stay, ask about safari packages, or request travel information.', 'contact Bardia, homestay booking, safari inquiry', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/contact', 'index,follow', 'published'),
('Activities & Wildlife Experiences', 'activities', '<h2>Wildlife Experiences in Bardia</h2>', 'Activities & Wildlife Experiences | Bardia Eco-Friendly Homestay', 'Explore 12 exciting wildlife experiences including Jeep Safaris, Walking Safaris, Birdwatching, and River Safaris in Bardia National Park.', 'wildlife activities Bardia, Jeep safari, walking safari, birdwatching, rafting', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/activities', 'index,follow', 'published'),
('Packages', 'packages', '<h2>Tour & Safari Packages</h2>', 'Tour & Safari Packages | Bardia Eco-Friendly Homestay', 'Browse our affordable homestay and wildlife safari packages in Bardia National Park.', 'Bardia packages, safari tour, homestay booking, eco lodge rates', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/packages', 'index,follow', 'published'),
('Gallery', 'gallery', '<h2>Photo & Video Gallery</h2>', 'Photo Gallery | Bardia Eco-Friendly Homestay', 'View photos of Royal Bengal Tigers, wildlife safaris, homestay rooms, and Tharu culture in Bardia.', 'Bardia photos, tiger pictures, safari gallery, homestay images', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/gallery', 'index,follow', 'published'),
('Blog', 'blog', '<h2>Blog & Wildlife Stories</h2>', 'Blog & Wildlife Stories | Bardia Eco-Friendly Homestay', 'Read travel guides, wildlife sighting updates, and stories about Bardia National Park.', 'Bardia blog, tiger sightings, safari tips, Nepal travel guide', '/logo.png', 'https://www.bardiaecofriendlyhomestay.com/blog', 'index,follow', 'published');



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
    `meta_title` VARCHAR(255)      DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `meta_keywords` VARCHAR(500)   DEFAULT NULL,
    `meta_image` VARCHAR(500)      DEFAULT NULL,
    `canonical_url` VARCHAR(500)   DEFAULT NULL,
    `robots` VARCHAR(50)           NOT NULL DEFAULT 'index,follow',
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
    `slug`          VARCHAR(100)   NOT NULL,
    `duration`      VARCHAR(50)    DEFAULT NULL,
    `price`         DECIMAL(10,2)  NOT NULL,
    `currency`      VARCHAR(5)     NOT NULL DEFAULT '₹',
    `price_note`    VARCHAR(50)    DEFAULT NULL,
    `description`   TEXT           NOT NULL,
    `meta_title` VARCHAR(255)      DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `meta_keywords` VARCHAR(500)   DEFAULT NULL,
    `meta_image` VARCHAR(500)      DEFAULT NULL,
    `canonical_url` VARCHAR(500)   DEFAULT NULL,
    `robots` VARCHAR(50)           NOT NULL DEFAULT 'index,follow',
    `is_featured`   TINYINT(1)     NOT NULL DEFAULT 0,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_packages_category` FOREIGN KEY (`category_id`) REFERENCES `package_categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_packages_category` (`category_id`),
    UNIQUE KEY `uk_packages_slug` (`slug`)
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
    `meta_title` VARCHAR(255)      DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `meta_keywords` VARCHAR(500)   DEFAULT NULL,
    `meta_image` VARCHAR(500)      DEFAULT NULL,
    `canonical_url` VARCHAR(500)   DEFAULT NULL,
    `robots` VARCHAR(50)           NOT NULL DEFAULT 'index,follow',
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
    `slug`          VARCHAR(100)   DEFAULT NULL,
    `alt_text`      VARCHAR(255)   DEFAULT NULL,
    `title_text`    VARCHAR(255)   DEFAULT NULL,
    `caption`       VARCHAR(500)   DEFAULT NULL,
    `width`         INT UNSIGNED   DEFAULT NULL,
    `height`        INT UNSIGNED   DEFAULT NULL,
    `meta_title` VARCHAR(255)      DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `meta_keywords` VARCHAR(500)   DEFAULT NULL,
    `meta_image` VARCHAR(500)      DEFAULT NULL,
    `canonical_url` VARCHAR(500)   DEFAULT NULL,
    `robots` VARCHAR(50)           NOT NULL DEFAULT 'index,follow',
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_galimg_category` FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uk_galimg_slug` (`slug`)
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
('default_keywords', 'Bardia, eco-friendly, homestay, Nepal, safari, wildlife, Bardiya National Park'),
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

-- ============================================================
-- 17. ACTIVITIES TABLE (Wildlife Experiences)
-- ============================================================
DROP TABLE IF EXISTS `activities`;

CREATE TABLE `activities` (
    `id`                INT            AUTO_INCREMENT PRIMARY KEY,
    `title`             VARCHAR(255)   NOT NULL,
    `slug`              VARCHAR(255)   NOT NULL,
    `icon`              VARCHAR(50)    DEFAULT NULL,
    `short_description` VARCHAR(500)   DEFAULT NULL,
    `description`       TEXT           NOT NULL,
    `meta_title`        VARCHAR(255)   DEFAULT NULL,
    `meta_description`  VARCHAR(500)   DEFAULT NULL,
    `meta_keywords`     VARCHAR(500)   DEFAULT NULL,
    `meta_image`        VARCHAR(500)   DEFAULT NULL,
    `canonical_url`     VARCHAR(500)   DEFAULT NULL,
    `robots`            VARCHAR(50)    NOT NULL DEFAULT 'index,follow',
    `display_order`     INT            NOT NULL DEFAULT 0,
    `is_active`         TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_activities_slug` (`slug`),
    INDEX `idx_activities_active` (`is_active`),
    INDEX `idx_activities_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wildlife experiences and activities';

INSERT INTO `activities` (`title`, `slug`, `icon`, `short_description`, `description`, `meta_title`, `meta_description`, `display_order`, `is_active`) VALUES
('Jeep Safari', 'jeep-safari', '🚙', 'Discover the diverse landscapes of Bardia National Park on a guided jeep safari.', 'Discover the diverse landscapes of Bardia National Park on a guided jeep safari. Travel deep into the wilderness in search of Royal Bengal Tigers, Greater One-horned Rhinoceroses, Wild Asian Elephants, Leopards, Sloth Bears, and many other species while learning about the park’s unique ecosystem from experienced naturalist guides.', 'Jeep Safari | Bardia Eco-Friendly Homestay', 'Discover the diverse landscapes of Bardia National Park on a guided jeep safari in search of Royal Bengal Tigers, Rhinos, and Elephants.', 1, 1),
('Guided Walking Safari', 'guided-walking-safari', '🥾', 'Experience the jungle at ground level with our expert local guides.', 'Experience the jungle at ground level with our expert local guides. Follow fresh animal tracks, listen to alarm calls, and gain a deeper understanding of wildlife behavior while exploring one of Nepal’s finest tiger habitats.', 'Guided Walking Safari | Bardia Eco-Friendly Homestay', 'Experience the jungle at ground level with expert local guides in Bardia National Park.', 2, 1),
('Birdwatching Excursion', 'birdwatching-excursion', '🦜', 'Home to more than 400 bird species, Bardia National Park is a paradise for bird lovers.', 'Home to more than 400 bird species, Bardia National Park is a paradise for bird lovers. From colorful kingfishers and hornbills to majestic eagles and rare species, our guided birding tours are suitable for both beginners and experienced birdwatchers.', 'Birdwatching Excursion | Bardia Eco-Friendly Homestay', 'Explore over 400 bird species in Bardia National Park with expert guided birding tours.', 3, 1),
('River Safari & Rafting', 'river-safari-rafting', '🛶', 'Float quietly along the Karnali River, enjoying breathtaking scenery and exceptional wildlife viewing.', 'Float quietly along the Karnali River, enjoying breathtaking scenery and exceptional wildlife viewing. Look for the endangered Ganges River Dolphin, Mugger Crocodiles, Gharials, otters, turtles, and a variety of water birds while experiencing the park from a unique perspective.', 'River Safari & Rafting | Bardia Eco-Friendly Homestay', 'Float quietly along the Karnali River to spot Ganges River Dolphins, Crocodiles, and aquatic birds.', 4, 1),
('Core Zone Wilderness Camping', 'core-zone-wilderness-camping', '⛺', 'Spend an unforgettable night in the heart of Bardia National Park’s wilderness.', 'Spend an unforgettable night in the heart of Bardia National Park’s wilderness. Surrounded by nature and accompanied by experienced guides, enjoy a truly immersive jungle experience under the stars while listening to the sounds of the forest.', 'Core Zone Wilderness Camping | Bardia Eco-Friendly Homestay', 'Spend an unforgettable night camping in the heart of Bardia National Park wilderness.', 5, 1),
('Treehouse Wilderness Stay', 'treehouse-wilderness-stay', '🏡', 'Stay overnight in our traditional treehouse located beside the community forest.', 'Stay overnight in our traditional treehouse located beside the community forest. Wake up to birdsong, enjoy peaceful surroundings, and experience nature from a unique and comfortable viewpoint.', 'Treehouse Wilderness Stay | Bardia Eco-Friendly Homestay', 'Overnight stay in a traditional treehouse beside the community forest in Bardia.', 6, 1),
('Ganges River Dolphin Expedition', 'ganges-river-dolphin-expedition', '🐬', 'Explore the Karnali River in search of the endangered Ganges River Dolphin.', 'Explore the Karnali River in search of the endangered Ganges River Dolphin, one of the world’s rarest freshwater dolphins. This unique wildlife experience offers excellent opportunities for photography and observing river ecosystems.', 'Ganges River Dolphin Expedition | Bardia Eco-Friendly Homestay', 'Search for the rare Ganges River Dolphin on Karnali River guided expeditions.', 7, 1),
('Community Forest Wildlife Safari', 'community-forest-wildlife-safari', '🌲', 'Discover the rich biodiversity of the nearby community forests.', 'Discover the rich biodiversity of the nearby community forests, home to deer, monkeys, numerous bird species, reptiles, and other wildlife. This peaceful safari also highlights the important role local communities play in conservation.', 'Community Forest Wildlife Safari | Bardia Eco-Friendly Homestay', 'Discover rich biodiversity and community conservation efforts in local forests near Bardia.', 8, 1),
('Tharu Cultural Experience', 'tharu-cultural-experience', '🪘', 'Immerse yourself in the traditions of the indigenous Tharu community.', 'Immerse yourself in the traditions of the indigenous Tharu community. Visit a traditional village, learn about their unique culture and lifestyle, and enjoy an authentic cultural dance performance in the evening.', 'Tharu Cultural Experience | Bardia Eco-Friendly Homestay', 'Immerse yourself in indigenous Tharu village life, traditional cuisine, and cultural dance.', 9, 1),
('Family Cooking Classes', 'family-cooking-classes', '🍲', 'Join our family in preparing authentic Nepali and Tharu dishes using fresh local ingredients.', 'Join our family in preparing authentic Nepali and Tharu dishes using fresh local ingredients. Learn traditional recipes, cooking techniques, and enjoy a delicious homemade meal together.', 'Family Cooking Classes | Bardia Eco-Friendly Homestay', 'Learn traditional Nepali and Tharu cooking techniques with fresh local ingredients.', 10, 1),
('Wildlife Photography Tours', 'wildlife-photography-tours', '📸', 'Designed for photographers of all skill levels to capture Bardia’s incredible wildlife.', 'Designed for photographers of all skill levels, these tours focus on maximizing opportunities to capture Bardia’s incredible wildlife, landscapes, and birdlife with guidance from experienced local guides.', 'Wildlife Photography Tours | Bardia Eco-Friendly Homestay', 'Specialized photography tours to capture tigers, wildlife, and landscapes in Bardia.', 11, 1),
('Airport Transfers', 'airport-transfers', '🚐', 'Enjoy comfortable and reliable transfers between Nepalgunj Airport and Bardia Eco-Friendly Homestay.', 'Enjoy comfortable and reliable transfers between Nepalgunj Airport and Bardia Eco-Friendly Homestay, ensuring a smooth and hassle-free start and end to your wildlife adventure.', 'Airport Transfers | Bardia Eco-Friendly Homestay', 'Hassle-free transfers between Nepalgunj Airport and Bardia Eco-Friendly Homestay.', 12, 1);

