-- SDSVG Community Directory — Seed Data
-- Run after schema.sql

SET NAMES utf8mb4;

-- Genders
INSERT IGNORE INTO genders (name, display_order, is_active) VALUES
  ('Male',   1, 1),
  ('Female', 2, 1),
  ('Other',  3, 1);

-- Relationships
INSERT IGNORE INTO relationships (name, display_order, is_active) VALUES
  ('Self',             1, 1),
  ('Wife',             2, 1),
  ('Son',              3, 1),
  ('Daughter',         4, 1),
  ('Son-in-Law',       5, 1),
  ('Daughter-in-Law',  6, 1),
  ('Grand Son',        7, 1),
  ('Grand Daughter',   8, 1),
  ('Other',            9, 1);

-- PDF Settings defaults
INSERT IGNORE INTO pdf_settings (setting_key, setting_value) VALUES
  ('community_full_name',   'SDSVG'),
  ('about_text',            ''),
  ('committee_text',        ''),
  ('cover_image_path',      ''),
  ('edition_date_override', ''),
  -- PDF front-matter visibility toggles ('1' = show, '0' = hidden; default hidden)
  ('pdf_show_cover',        '0'),
  ('pdf_show_intro',        '0'),
  ('pdf_show_committee',    '0');
