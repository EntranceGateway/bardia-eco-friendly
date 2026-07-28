-- ============================================================
-- Bardiya Eco Friendly - Activities Table & Seed Data
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
