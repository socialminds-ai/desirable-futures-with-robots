-- Switch facilitator location to structured city + country, and add a
-- cities lookup table for offline (self-hosted) geocoding.

ALTER TABLE facilitators
    ADD COLUMN city VARCHAR(120) NULL AFTER institution,
    DROP COLUMN continent,
    DROP COLUMN location_label;

CREATE TABLE cities (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(200)  NOT NULL,   -- UTF-8 name (e.g. Zürich)
    asciiname  VARCHAR(200)  NOT NULL,   -- ASCII form (e.g. Zurich)
    country    VARCHAR(100)  NOT NULL,   -- country name, matches lib/countries.php
    lat        DECIMAL(8,5)  NOT NULL,
    lng        DECIMAL(8,5)  NOT NULL,
    population INT           NOT NULL DEFAULT 0,
    KEY idx_country_name  (country, name),
    KEY idx_country_ascii (country, asciiname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
