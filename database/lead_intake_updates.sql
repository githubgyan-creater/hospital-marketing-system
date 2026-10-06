-- Hospital Marketing System
-- Lead intake changes: Bulk Import + Public Forms + Telecaller role restriction
-- Run this against the existing hospital_marketing database.

CREATE TABLE IF NOT EXISTS `lead_forms` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_token` varchar(64) NOT NULL,
  `form_name` varchar(150) NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lead_forms_token` (`form_token`),
  KEY `idx_lead_forms_created_by` (`created_by`),
  KEY `idx_lead_forms_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `marketing_sources` (`name`, `status`)
SELECT 'Bulk Import', 'ACTIVE'
WHERE NOT EXISTS (
    SELECT 1 FROM `marketing_sources` WHERE `name` = 'Bulk Import'
);

INSERT INTO `marketing_sources` (`name`, `status`)
SELECT 'Public Form', 'ACTIVE'
WHERE NOT EXISTS (
    SELECT 1 FROM `marketing_sources` WHERE `name` = 'Public Form'
);

-- Telecallers must not create leads.
DELETE rp
FROM `role_permissions` rp
INNER JOIN `roles` r ON r.id = rp.role_id AND r.name = 'telecaller'
INNER JOIN `permissions` p ON p.id = rp.permission_id AND p.name = 'leads.create';
