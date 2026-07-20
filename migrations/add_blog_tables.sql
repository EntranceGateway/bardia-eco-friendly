-- ============================================================
-- Blog Module — Database Migration
-- Adds blog_categories and blog_posts tables
-- Run: mysql -u root bardiya_eco_friendly < migrations/add_blog_tables.sql
-- ============================================================

-- ============================================================
-- 1. BLOG CATEGORIES TABLE
-- ============================================================
DROP TABLE IF EXISTS `blog_posts`;
DROP TABLE IF EXISTS `blog_categories`;

CREATE TABLE `blog_categories` (
    `id`            BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100)   NOT NULL,
    `slug`          VARCHAR(100)   NOT NULL,
    `description`   TEXT           DEFAULT NULL,
    `display_order` INT            NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_blogcat_slug` (`slug`),
    INDEX `idx_blogcat_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Blog post categories';

-- ============================================================
-- 2. BLOG POSTS TABLE
-- ============================================================
CREATE TABLE `blog_posts` (
    `id`                  BIGINT         AUTO_INCREMENT PRIMARY KEY,
    `category_id`         BIGINT         NOT NULL,
    `title`               VARCHAR(255)   NOT NULL,
    `slug`                VARCHAR(255)   NOT NULL,
    `excerpt`             VARCHAR(500)   DEFAULT NULL COMMENT 'Short summary for listing pages and meta description fallback',
    `content`             LONGTEXT       DEFAULT NULL COMMENT 'Full HTML body of the blog post',
    `featured_image`      VARCHAR(500)   DEFAULT NULL COMMENT 'Relative path e.g. /storage/blog/filename.jpg',
    `featured_image_alt`  VARCHAR(255)   DEFAULT NULL COMMENT 'Alt text for featured image',
    `author_name`         VARCHAR(100)   DEFAULT NULL,

    -- SEO fields (matching pages table pattern)
    `meta_title`          VARCHAR(255)   DEFAULT NULL,
    `meta_description`    VARCHAR(500)   DEFAULT NULL,
    `meta_keywords`       VARCHAR(500)   DEFAULT NULL,
    `meta_image`          VARCHAR(500)   DEFAULT NULL,
    `canonical_url`       VARCHAR(500)   DEFAULT NULL,
    `robots`              VARCHAR(50)    NOT NULL DEFAULT 'index,follow',

    `is_featured`         TINYINT(1)     NOT NULL DEFAULT 0,
    `status`              ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `published_at`        DATETIME       DEFAULT NULL COMMENT 'Set when status changes to published',
    `display_order`       INT            NOT NULL DEFAULT 0,
    `is_active`           TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_blogpost_slug` (`slug`),
    INDEX `idx_blogpost_status` (`status`),
    INDEX `idx_blogpost_category` (`category_id`),
    INDEX `idx_blogpost_published` (`published_at`),
    INDEX `idx_blogpost_featured` (`is_featured`),
    INDEX `idx_blogpost_active` (`is_active`, `status`),

    CONSTRAINT `fk_blogpost_category` FOREIGN KEY (`category_id`)
        REFERENCES `blog_categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Blog posts with full SEO support';

-- ============================================================
-- 3. SEED DATA
-- ============================================================
INSERT INTO `blog_categories` (`name`, `slug`, `description`, `display_order`, `is_active`) VALUES
('Wildlife & Conservation', 'wildlife-conservation', 'Articles about Bardiya National Park wildlife, conservation efforts, and safari experiences.', 1, 1),
('Travel Tips', 'travel-tips', 'Practical travel advice, packing lists, and tips for visiting Bardiya.', 2, 1);

INSERT INTO `blog_posts` (`category_id`, `title`, `slug`, `excerpt`, `content`, `author_name`, `meta_title`, `meta_description`, `robots`, `is_featured`, `status`, `published_at`, `display_order`, `is_active`) VALUES
(1, 'Spotting the Royal Bengal Tiger in Bardiya', 'spotting-royal-bengal-tiger-bardiya',
 'Bardiya National Park is one of the best places in Nepal to spot the elusive Royal Bengal Tiger. Learn about the best seasons, safari routes, and tips for a successful sighting.',
 '<h2>The Thrill of Tiger Spotting</h2><p>Bardiya National Park, nestled in the western Terai of Nepal, is home to a growing population of Royal Bengal Tigers. Unlike the more crowded Chitwan, Bardiya offers a more intimate and authentic wildlife experience.</p><h2>Best Time to Visit</h2><p>The ideal season for tiger spotting is from October to March, when the tall grasses are cut and visibility improves dramatically. Early morning jeep safaris and guided jungle walks provide the best chances of an encounter.</p><h2>Safari Routes</h2><p>The park offers several safari options including jeep safaris through the core zone, elephant-back safaris, and guided walking tours along the Karnali River. Each offers unique perspectives on the park''s diverse ecosystem.</p>',
 'Bardiya Eco Team',
 'Spotting the Royal Bengal Tiger in Bardiya National Park | Bardiya Eco Friendly',
 'Discover tips for spotting Royal Bengal Tigers in Bardiya National Park, Nepal. Best seasons, safari routes, and expert advice for wildlife enthusiasts.',
 'index,follow', 1, 'published', NOW(), 1, 1);
