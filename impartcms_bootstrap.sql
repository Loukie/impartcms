-- ImpartCMS bootstrap SQL (MySQL/MariaDB)
-- NOTE: assumes Laravel default users table already exists.
-- Import this into your DB (e.g. phpMyAdmin) AFTER creating the Laravel project DB.

START TRANSACTION;

-- Add admin flag to users (will fail if column already exists)
ALTER TABLE `users` ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`;

CREATE TABLE IF NOT EXISTS `pages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `body` LONGTEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `template` VARCHAR(100) NOT NULL DEFAULT 'default',
  `is_homepage` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_meta` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_id` BIGINT UNSIGNED NOT NULL,
  `meta_title` VARCHAR(255) NULL,
  `meta_description` TEXT NULL,
  `canonical_url` VARCHAR(500) NULL,
  `robots` VARCHAR(255) NULL,
  `og_title` VARCHAR(255) NULL,
  `og_description` TEXT NULL,
  `og_image_url` VARCHAR(500) NULL,
  `twitter_title` VARCHAR(255) NULL,
  `twitter_description` TEXT NULL,
  `twitter_image_url` VARCHAR(500) NULL,
  `extras` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_meta_page_id_unique` (`page_id`),
  CONSTRAINT `seo_meta_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `modules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `provider_class` VARCHAR(255) NOT NULL,
  `version` VARCHAR(50) NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `settings` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `forms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `fields` JSON NULL,
  `settings` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forms_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `form_recipient_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` BIGINT UNSIGNED NOT NULL,
  `page_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `recipients` JSON NOT NULL,
  `from_name` VARCHAR(255) NULL,
  `from_email` VARCHAR(255) NULL,
  `reply_to_email` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fr_rules_idx` (`form_id`,`page_id`,`user_id`),
  CONSTRAINT `fr_rules_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fr_rules_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fr_rules_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `form_submissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` BIGINT UNSIGNED NOT NULL,
  `page_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `payload` JSON NOT NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(1000) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fs_idx` (`form_id`,`created_at`),
  CONSTRAINT `fs_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fs_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starter data (safe to delete)
INSERT INTO `pages` (`id`,`title`,`slug`,`body`,`status`,`template`,`is_homepage`,`published_at`,`created_at`,`updated_at`)
VALUES
  (1,'Home','home','Welcome 👋\n\nHere is a contact form:\n\n[form slug="contact"]','published','default',1,NOW(),NOW(),NOW())
ON DUPLICATE KEY UPDATE `updated_at`=VALUES(`updated_at`);

INSERT INTO `forms` (`id`,`name`,`slug`,`fields`,`settings`,`is_active`,`created_at`,`updated_at`)
VALUES
  (1,'Contact Us','contact',
   JSON_ARRAY(
     JSON_OBJECT('name','name','type','text','label','Your name','required',true),
     JSON_OBJECT('name','email','type','email','label','Your email','required',true),
     JSON_OBJECT('name','message','type','textarea','label','Message','required',true)
   ),
   JSON_OBJECT('default_recipients', JSON_ARRAY('you@example.com')),
   1,
   NOW(),NOW()
  )
ON DUPLICATE KEY UPDATE `updated_at`=VALUES(`updated_at`);

COMMIT;
