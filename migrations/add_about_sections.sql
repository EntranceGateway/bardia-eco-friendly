-- About Us page content, editable from CMS → About Page.
-- One table for every block of the page; `section_type` decides where a row renders:
--   hero  : first active row = page hero (title, subtitle as intro, image)
--   story : first active row = story block (title, body, subtitle as pull quote, image)
--   value : list of values / commitments (title, body, optional icon)
--   team  : hosts & team members (title = name, subtitle = role, body = short bio, image = photo)

CREATE TABLE IF NOT EXISTS `about_sections` (
    `id`            INT           AUTO_INCREMENT PRIMARY KEY,
    `section_type`  VARCHAR(20)   NOT NULL COMMENT 'hero|story|value|team',
    `title`         VARCHAR(255)  NOT NULL,
    `subtitle`      VARCHAR(500)  DEFAULT NULL,
    `body`          TEXT          DEFAULT NULL,
    `image_url`     VARCHAR(500)  DEFAULT NULL,
    `image_alt`     VARCHAR(255)  DEFAULT NULL,
    `icon`          VARCHAR(1000) DEFAULT NULL,
    `display_order` INT           NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_about_type_order` (`section_type`, `is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='About Us page blocks';

INSERT INTO `about_sections` (`section_type`, `title`, `subtitle`, `body`, `image_url`, `image_alt`, `icon`, `display_order`) VALUES
('hero', 'A Family Home at the Edge of the Jungle',
 'We are a small, locally run homestay in Thakurdwara, a short walk from Bardia National Park.',
 NULL, '/gallery/homestay.jpg', 'Bardia Eco-Friendly Homestay surrounded by green trees', NULL, 1),

('story', 'Why We Opened Our Home',
 'We want every guest to leave knowing the forest a little better, and caring about it a little more.',
 'Bardia Eco-Friendly Homestay started as our family home on the edge of the park. Travelers kept asking where to stay close to the tigers, so we opened our doors.\n\nToday our guests sleep in simple, comfortable rooms, eat vegetables from our own farm, and head into the jungle with guides who grew up beside it.\n\nWe keep things small on purpose. Fewer guests means quieter safaris, real conversations around the fire, and less pressure on the forest we depend on.',
 '/gallery/homestay1.jpg', 'Guest area at Bardia Eco-Friendly Homestay', NULL, 1),

('value', 'Wildlife Comes First', NULL,
 'Our safaris follow park rules and keep a respectful distance. A good sighting is never worth stressing an animal.',
 NULL, NULL, 'binoculars', 1),
('value', 'Local Hands, Local Knowledge', NULL,
 'Our guides, cooks and staff are from Bardia. Your stay supports families in the village, not distant investors.',
 NULL, NULL, 'hiking', 2),
('value', 'Food From Our Own Soil', NULL,
 'Most of what you eat is grown on our organic farm, a few steps from the dining table.',
 NULL, NULL, NULL, 3),
('value', 'Small by Design', NULL,
 'A handful of rooms keeps the homestay calm and lets us give every guest real attention.',
 NULL, NULL, 'compass', 4),

('team', 'Our Naturalist Guides', 'Jungle walks and jeep safaris',
 'Licensed guides who know the tracks, calls and habits of Bardia''s tigers, rhinos and elephants.',
 '/gallery/tourguide.jpg', 'Naturalist guide in Bardia National Park', NULL, 1),
('team', 'The Homestay Family', 'Your hosts',
 'We look after your rooms, your plans and your questions from the moment you arrive.',
 '/gallery/homestay2.jpg', 'The homestay grounds', NULL, 2),
('team', 'Our Kitchen', 'Farm-to-table cooking',
 'Home-style Nepali and Tharu meals, cooked fresh every day with produce from our farm.',
 '/gallery/homestay3.jpg', 'Homestay kitchen and dining area', NULL, 3);
