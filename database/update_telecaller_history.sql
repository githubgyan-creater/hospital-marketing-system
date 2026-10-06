-- Hospital Marketing System
-- Migration: persistent Telecaller follow-up history + role guardrail
-- Run once in phpMyAdmin on the existing `hospital_marketing` database.

CREATE TABLE IF NOT EXISTS `lead_followups` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `followup_type` varchar(100) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `status` enum('Pending','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(10) UNSIGNED DEFAULT NULL,
  `completion_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lead_followups_lead` (`lead_id`),
  KEY `fk_lead_followups_user` (`user_id`),
  KEY `idx_lead_followups_status_due` (`status`,`scheduled_at`),
  KEY `fk_lead_followups_completed_by` (`completed_by`),
  CONSTRAINT `fk_lead_followups_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lead_followups_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lead_followups_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Preserve the current next action already stored on existing leads.
-- Older overwritten follow-ups cannot be reconstructed exactly, but the
-- current outstanding action is copied into the new history table.
INSERT INTO lead_followups (lead_id, user_id, followup_type, scheduled_at, status, notes)
SELECT
    l.id,
    l.assigned_to,
    l.next_action_type,
    l.next_action_at,
    'Pending',
    l.notes
FROM leads l
WHERE l.assigned_to IS NOT NULL
  AND l.next_action_type IS NOT NULL
  AND l.next_action_type <> ''
  AND l.next_action_at IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM lead_followups lf
      WHERE lf.lead_id = l.id
        AND lf.user_id = l.assigned_to
        AND lf.status = 'Pending'
        AND lf.followup_type = l.next_action_type
        AND lf.scheduled_at = l.next_action_at
  );

-- Telecallers must work assigned leads; lead creation/editing remains with
-- Admin / Manager / Marketing Executive.
DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.name = 'telecaller'
  AND p.name IN ('leads.create', 'leads.edit');

-- Existing leads can have only one current next action, so old overwritten
-- follow-ups cannot be reconstructed exactly. Future actions are fully retained.
