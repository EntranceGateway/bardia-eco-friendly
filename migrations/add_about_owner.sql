-- Owner profile on the About Us page (CMS → About Page → "Owner Profile").
-- Run after add_about_sections.sql.
--   title    = name
--   subtitle = roles, separated by |
--   body     = bio paragraphs, then one highlight per line starting with "•" or "-" ("Label: text")
--   phone / email = shown as contact links; WhatsApp uses the phone number

ALTER TABLE `about_sections`
    ADD COLUMN `phone` VARCHAR(50)  DEFAULT NULL AFTER `icon`,
    ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `phone`;

INSERT INTO `about_sections` (`section_type`, `title`, `subtitle`, `body`, `image_url`, `image_alt`, `phone`, `email`, `display_order`) VALUES
('owner', 'Shardul Bhattarai',
 'Wildlife Guide | Safari Specialist | Wildlife Photographer | Human-Wildlife Coexistence Advocate',
 'Shardul Bhattarai is a passionate wildlife professional based in the Bardia region of Nepal, with experience in wildlife tourism since 2014. Through his work as a safari guide, photographer, and tourism entrepreneur, he is dedicated to showcasing Nepal''s rich biodiversity while promoting responsible wildlife tourism and conservation awareness.\n\n• Wildlife & Safari Expertise: Experienced in wildlife tracking, tiger observation, and guiding visitors through the natural habitats of Bardia National Park.\n• Human-Wildlife Conflict Support: Committed to supporting people affected by human-wildlife conflict and advocating for practical solutions that protect local communities while conserving wildlife.\n• Wildlife Photography: Captures the beauty, behaviour, and ecological importance of wildlife to inspire conservation awareness.\n• Sustainable Tourism: Owner of Bardia Eco Friendly Homestay, promoting authentic local hospitality, nature-based tourism, and meaningful cultural experiences.\n• Communication Skills: Excellent English communication skills and the ability to speak Japanese, helping connect international visitors with Nepal''s wildlife and local communities.\n• Conservation Vision: Dedicated to strengthening the relationship between people and nature, encouraging peaceful coexistence, and promoting the long-term conservation of Bardia''s unique ecosystems.',
 '/gallery/shardul-bhattarai.webp', 'Shardul Bhattarai with binoculars beside a safari jeep in Bardia National Park',
 '+977 9842364787', 'shardul.bhattarai@gmail.com', 1);
