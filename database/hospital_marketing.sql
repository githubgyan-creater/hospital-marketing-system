-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2026 at 09:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hospital_marketing`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `appointment_date` datetime NOT NULL,
  `appointment_type` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Scheduled','Confirmed','Completed','Cancelled','No Show') NOT NULL DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `lead_id`, `created_by`, `appointment_date`, `appointment_type`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 4, '2026-09-24 18:50:00', 'Doctor Visit', 'Patient requested an appointment.', 'Scheduled', '2026-09-24 12:20:16', '2026-09-24 12:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `campaign_type` enum('Digital','Health Camp','Corporate Outreach','Referral','Service Promotions','Community Outrich','Event','Other') NOT NULL DEFAULT 'Other',
  `objective` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT 0.00,
  `status` enum('Draft','Planned','Active','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
  `description` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`id`, `name`, `campaign_type`, `objective`, `start_date`, `end_date`, `budget`, `status`, `description`, `created_by`, `created_at`, `update_at`) VALUES
(1, 'World heart day', 'Health Camp', 'Increase cardiac consultation enquiries', '2026-09-26', '2026-09-26', 50000.00, 'Planned', 'Test campaign.', 2, '2026-09-25 11:10:55', '2026-09-25 11:20:27');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_name` varchar(150) NOT NULL,
  `department_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `department_code`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Cardiology', 'CARD', 'Cardiology Department .', 'ACTIVE', '2026-09-29 07:33:34', '2026-09-29 07:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(10) UNSIGNED NOT NULL,
  `doctor_name` varchar(150) NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `experience_years` int(10) UNSIGNED DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `doctor_name`, `department_id`, `specialization`, `qualification`, `experience_years`, `phone`, `email`, `consultation_fee`, `bio`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Dr Rahul Sharma', 1, 'Interventional Cardiology', 'MBBS , MD', 11, '9984753768', 'rahul@gmail.com', 999.98, 'N/A', 'ACTIVE', '2026-09-29 07:46:19', '2026-09-29 07:47:06');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `event_type` enum('Health Camp','Outreach','Seminar','Workshop','Doctor Meet','Corporate Event','Other') NOT NULL DEFAULT 'Other',
  `event_date` date NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Planned','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `event_type`, `event_date`, `location`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Free Cardiology Health Camp 2.o', 'Health Camp', '2026-09-30', 'Bhopal Community Hall', 'Free cardiac screening and patient awareness activity.', 'Planned', 2, '2026-09-26 09:34:57', '2026-09-26 10:04:28');

-- --------------------------------------------------------

--
-- Table structure for table `event_assignments`
--

CREATE TABLE `event_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `assigned_by` int(10) UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_assignments`
--

INSERT INTO `event_assignments` (`id`, `event_id`, `user_id`, `assigned_by`, `assigned_at`) VALUES
(1, 1, 4, 2, '2026-09-26 10:50:56'),
(2, 1, 3, 2, '2026-09-26 11:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `event_leads`
--

CREATE TABLE `event_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `captured_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_leads`
--

INSERT INTO `event_leads` (`id`, `event_id`, `lead_id`, `captured_by`, `created_at`) VALUES
(1, 1, 4, 4, '2026-09-26 11:09:25');

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE `hospitals` (
  `id` int(10) UNSIGNED NOT NULL,
  `hospital_name` varchar(200) NOT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `service_interest` varchar(200) DEFAULT NULL,
  `source_id` int(10) UNSIGNED DEFAULT NULL,
  `campaign_id` int(10) UNSIGNED DEFAULT NULL,
  `referral_id` int(10) UNSIGNED DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'NEW',
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `next_action_type` varchar(100) DEFAULT NULL,
  `next_action_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `name`, `phone`, `email`, `service_interest`, `source_id`, `campaign_id`, `referral_id`, `status`, `priority`, `assigned_to`, `next_action_type`, `next_action_at`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Rahul Sharma', '9984753768', 'rahul@example.com', 'Orthopaedic Consultation', 1, NULL, NULL, 'New', 'High', 3, NULL, NULL, 'Interested in consultation.', 1, '2026-09-24 07:45:24', '2026-09-28 11:27:48'),
(2, 'Rahul Sharma', '9876543210', 'rahul.sharma@example.com', 'Orthopedic Consultation', 2, 1, NULL, 'New', 'High', 4, NULL, NULL, 'Requested a callback regarding consultation details', 2, '2026-09-25 12:16:26', '2026-09-25 12:16:26'),
(3, 'Test Referral Lead', '12345657890', 'test@gmail.com', NULL, 7, 1, 1, 'New', 'High', 3, NULL, NULL, 'N/A', 2, '2026-09-26 07:07:35', '2026-09-28 11:27:50'),
(4, 'Amit Kumar', '9876501234', 'amit@example.com', 'Cardiology', NULL, NULL, NULL, 'New', 'High', 4, NULL, NULL, 'Interested in consultation after health camp.', 4, '2026-09-26 11:09:25', '2026-09-26 11:09:25');

-- --------------------------------------------------------

--
-- Table structure for table `lead_activities`
--

CREATE TABLE `lead_activities` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `activity_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_activities`
--

INSERT INTO `lead_activities` (`id`, `lead_id`, `user_id`, `activity_type`, `description`, `activity_at`, `created_at`) VALUES
(1, 1, 1, 'Visit', 'visits', '2026-09-24 14:58:25', '2026-09-24 09:28:25'),
(2, 1, 1, 'Call', 'take calls', '2026-09-24 15:49:00', '2026-09-24 10:19:00'),
(3, 1, 4, 'Call', 'Call outcome: Connected. NONE', '2026-09-24 17:44:30', '2026-09-24 12:14:30'),
(4, 1, 4, 'Appointment', 'Appointment created for 24 Sep 2026, 06:50 PM. Type: Doctor Visit. Notes: Patient requested an appointment.', '2026-09-24 17:50:16', '2026-09-24 12:20:16'),
(5, 1, 2, 'Lead Assigned', 'Lead assigned to Vijay (marketing).', '2026-09-25 13:15:10', '2026-09-25 07:45:10'),
(6, 1, 2, 'Lead Assigned', 'Lead assigned to Rajesh Sharma (telecaller).', '2026-09-25 13:16:38', '2026-09-25 07:46:38'),
(7, 1, 2, 'Lead Assigned', 'Lead assigned to Vijay (marketing).', '2026-09-25 13:17:00', '2026-09-25 07:47:00'),
(8, 2, 2, 'Lead Created', 'Lead was created.', '2026-09-25 17:46:26', '2026-09-25 12:16:26'),
(9, 2, 2, 'Campaign Assigned', 'Lead associated with campaign: World heart day.', '2026-09-25 17:46:26', '2026-09-25 12:16:26'),
(10, 2, 2, 'Lead Assigned', 'Lead assigned to Rajesh Sharma.', '2026-09-25 17:46:26', '2026-09-25 12:16:26'),
(11, 1, 2, 'Lead Assigned', 'Lead assigned to Rajesh Sharma (telecaller).', '2026-09-25 18:06:08', '2026-09-25 12:36:08'),
(12, 1, 2, 'Lead Assigned', 'Lead assigned to Vijay (marketing).', '2026-09-25 18:06:16', '2026-09-25 12:36:16'),
(13, 1, 2, 'Lead Assigned', 'Lead assigned to Vijay (marketing).', '2026-09-25 18:08:25', '2026-09-25 12:38:25'),
(14, 3, 2, 'Lead Created', 'Lead created by Shourya.', '2026-09-26 12:37:35', '2026-09-26 07:07:35'),
(15, 3, 2, 'Lead Assigned', 'Lead assigned to Vijay.', '2026-09-26 12:37:35', '2026-09-26 07:07:35'),
(16, 3, 2, 'Campaign Assigned', 'Lead linked to campaign: World heart day.', '2026-09-26 12:37:35', '2026-09-26 07:07:35'),
(17, 3, 2, 'Referral Assigned', 'Lead linked to referral partner: Dr. Test - Test Clinic.', '2026-09-26 12:37:35', '2026-09-26 07:07:35'),
(18, 4, 4, 'Lead Created', 'Lead captured from event: Free Cardiology Health Camp 2.o.', '2026-09-26 16:39:25', '2026-09-26 11:09:25'),
(19, 4, 4, 'Lead Assigned', 'Lead automatically assigned to Rajesh Sharma after event capture.', '2026-09-26 16:39:25', '2026-09-26 11:09:25'),
(20, 1, 2, 'Status Changed', 'Lead status changed from Appointment Requested to New.', '2026-09-26 17:29:24', '2026-09-26 11:59:24'),
(21, 3, 2, 'Marketing Visit Planned', 'Marketing visit planned: Meet Apollo Clinic Coordinator (Clinic Visit).', '2026-09-28 14:00:26', '2026-09-28 08:30:26'),
(22, 1, 3, 'Marketing Follow-up Completed', 'Marketing follow-up completed', '2026-09-28 16:57:48', '2026-09-28 11:27:48'),
(23, 3, 3, 'Marketing Follow-up Completed', 'Marketing follow-up completed. Previous action: call', '2026-09-28 16:57:50', '2026-09-28 11:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `lead_calls`
--

CREATE TABLE `lead_calls` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `call_outcome` enum('Connected','Not Connected','Call Back','Interested','Not Interested','Appointment Requested') NOT NULL,
  `Call_notes` text DEFAULT NULL,
  `next_action_type` varchar(100) DEFAULT NULL,
  `next_action_at` datetime DEFAULT NULL,
  `call_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_calls`
--

INSERT INTO `lead_calls` (`id`, `lead_id`, `user_id`, `call_outcome`, `Call_notes`, `next_action_type`, `next_action_at`, `call_at`, `created_at`) VALUES
(1, 1, 4, 'Connected', 'NONE', 'Appointment', '2026-09-23 01:45:00', '2026-09-24 17:44:30', '2026-09-24 12:14:30');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_plans`
--

CREATE TABLE `marketing_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_title` varchar(200) NOT NULL,
  `objective` text DEFAULT NULL,
  `focus_area` varchar(200) DEFAULT NULL,
  `target_description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Draft','Active','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
  `remarks` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketing_plans`
--

INSERT INTO `marketing_plans` (`id`, `plan_title`, `objective`, `focus_area`, `target_description`, `start_date`, `end_date`, `status`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'October Doctor Referral Campaign', 'Increase doctor referral enquiries for the hospital.', 'Doctor Relationship', 'Visit 30 doctors and generate 10 referral enquiries.', '2026-10-01', '2026-10-03', 'Draft', 'Initial marketing plan for October.', 2, '2026-09-28 12:06:11', '2026-09-28 12:11:53');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_sources`
--

CREATE TABLE `marketing_sources` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketing_sources`
--

INSERT INTO `marketing_sources` (`id`, `name`, `status`, `created_at`) VALUES
(1, 'Website', 'ACTIVE', '2026-09-24 06:47:57'),
(2, 'Google', 'ACTIVE', '2026-09-24 06:47:57'),
(3, 'Social Media', 'ACTIVE', '2026-09-24 06:47:57'),
(4, 'Phone', 'ACTIVE', '2026-09-24 06:47:57'),
(5, 'Whatsapp', 'ACTIVE', '2026-09-24 06:47:57'),
(6, 'Referral', 'ACTIVE', '2026-09-24 06:47:57'),
(7, 'Health Camp', 'ACTIVE', '2026-09-24 06:47:57'),
(8, 'Corporate', 'ACTIVE', '2026-09-24 06:47:57'),
(9, 'Other', 'ACTIVE', '2026-09-24 06:47:57');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_visits`
--

CREATE TABLE `marketing_visits` (
  `id` int(10) UNSIGNED NOT NULL,
  `visit_type` enum('Doctor Visit','Clinic Visit','Corporate Visit','Field Visit') NOT NULL,
  `title` varchar(200) NOT NULL,
  `person_name` varchar(150) DEFAULT NULL,
  `organization_name` varchar(200) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `location` varchar(250) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `visit_date` datetime NOT NULL,
  `assigned_to` int(10) UNSIGNED NOT NULL,
  `related_task_id` int(10) UNSIGNED DEFAULT NULL,
  `related_lead_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('Planned','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
  `outcome` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `next_action_type` varchar(100) DEFAULT NULL,
  `next_action_at` datetime DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketing_visits`
--

INSERT INTO `marketing_visits` (`id`, `visit_type`, `title`, `person_name`, `organization_name`, `phone`, `email`, `location`, `purpose`, `visit_date`, `assigned_to`, `related_task_id`, `related_lead_id`, `status`, `outcome`, `remarks`, `next_action_type`, `next_action_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Clinic Visit', 'Meet Apollo Clinic Coordinator', 'Clinic Coordinator', 'Apollo Clinic', '12345657890', NULL, 'delhi', NULL, '2026-09-29 13:00:00', 3, 1, 3, 'Planned', NULL, NULL, NULL, NULL, 2, '2026-09-28 08:30:26', '2026-09-28 08:30:26');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `module` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `module`, `created_at`) VALUES
(1, 'users.view', 'View Users', 'Users', '2026-09-29 06:37:37'),
(2, 'users.create', 'Create Users', 'Users', '2026-09-29 06:37:37'),
(3, 'users.edit', 'Edit Users', 'Users', '2026-09-29 06:37:37'),
(4, 'users.delete', 'Delete Users', 'Users', '2026-09-29 06:37:37'),
(5, 'leads.view', 'View Leads', 'Leads', '2026-09-29 06:37:37'),
(6, 'leads.create', 'Create Leads', 'Leads', '2026-09-29 06:37:37'),
(7, 'leads.edit', 'Edit Leads', 'Leads', '2026-09-29 06:37:37'),
(8, 'leads.delete', 'Delete Leads', 'Leads', '2026-09-29 06:37:37'),
(9, 'leads.assign', 'Assign Leads', 'Leads', '2026-09-29 06:37:37'),
(10, 'calls.view', 'View Calls', 'Calls', '2026-09-29 06:37:37'),
(11, 'calls.create', 'Record Calls', 'Calls', '2026-09-29 06:37:37'),
(12, 'followups.view', 'View Follow-ups', 'Follow-ups', '2026-09-29 06:37:37'),
(13, 'followups.create', 'Create Follow-ups', 'Follow-ups', '2026-09-29 06:37:37'),
(14, 'followups.edit', 'Edit Follow-ups', 'Follow-ups', '2026-09-29 06:37:37'),
(15, 'appointments.view', 'View Appointments', 'Appointments', '2026-09-29 06:37:37'),
(16, 'appointments.create', 'Create Appointments', 'Appointments', '2026-09-29 06:37:37'),
(17, 'appointments.edit', 'Edit Appointments', 'Appointments', '2026-09-29 06:37:37'),
(18, 'tasks.view', 'View Tasks', 'Tasks', '2026-09-29 06:37:37'),
(19, 'tasks.create', 'Create Tasks', 'Tasks', '2026-09-29 06:37:37'),
(20, 'tasks.edit', 'Edit Tasks', 'Tasks', '2026-09-29 06:37:37'),
(21, 'tasks.delete', 'Delete Tasks', 'Tasks', '2026-09-29 06:37:37'),
(22, 'visits.view', 'View Visits', 'Visits', '2026-09-29 06:37:37'),
(23, 'visits.create', 'Create Visits', 'Visits', '2026-09-29 06:37:37'),
(24, 'visits.edit', 'Edit Visits', 'Visits', '2026-09-29 06:37:37'),
(25, 'visits.delete', 'Delete Visits', 'Visits', '2026-09-29 06:37:37'),
(26, 'campaigns.view', 'View Campaigns', 'Campaigns', '2026-09-29 06:37:37'),
(27, 'campaigns.create', 'Create Campaigns', 'Campaigns', '2026-09-29 06:37:37'),
(28, 'campaigns.edit', 'Edit Campaigns', 'Campaigns', '2026-09-29 06:37:37'),
(29, 'campaigns.delete', 'Delete Campaigns', 'Campaigns', '2026-09-29 06:37:37'),
(30, 'referrals.view', 'View Referrals', 'Referrals', '2026-09-29 06:37:37'),
(31, 'referrals.create', 'Create Referrals', 'Referrals', '2026-09-29 06:37:37'),
(32, 'referrals.edit', 'Edit Referrals', 'Referrals', '2026-09-29 06:37:37'),
(33, 'referrals.delete', 'Delete Referrals', 'Referrals', '2026-09-29 06:37:37'),
(34, 'events.view', 'View Events', 'Events', '2026-09-29 06:37:37'),
(35, 'events.create', 'Create Events', 'Events', '2026-09-29 06:37:37'),
(36, 'events.edit', 'Edit Events', 'Events', '2026-09-29 06:37:37'),
(37, 'events.delete', 'Delete Events', 'Events', '2026-09-29 06:37:37'),
(38, 'reports.view', 'View Reports', 'Reports', '2026-09-29 06:37:37'),
(39, 'marketing_plans.view', 'View Marketing Plans', 'Marketing Plans', '2026-09-29 06:37:37'),
(40, 'marketing_plans.create', 'Create Marketing Plans', 'Marketing Plans', '2026-09-29 06:37:37'),
(41, 'marketing_plans.edit', 'Edit Marketing Plans', 'Marketing Plans', '2026-09-29 06:37:37'),
(42, 'management.review', 'View Management Review', 'Management', '2026-09-29 06:37:37'),
(43, 'management.action_required', 'View Action Required', 'Management', '2026-09-29 06:37:37'),
(44, 'settings.view', 'View Settings', 'Settings', '2026-09-29 06:37:37'),
(45, 'settings.edit', 'Edit Settings', 'Settings', '2026-09-29 06:37:37');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `referral_type` enum('Doctor','Clinic','Hospital','Corporate','Health Professional','Other') NOT NULL DEFAULT 'Other',
  `organization` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`id`, `name`, `referral_type`, `organization`, `phone`, `email`, `address`, `specialty`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Dr. Test', 'Doctor', 'Test Clinic', '9984753768', 'test@gmail.com', 'lko', 'Cardiology', 'Active', NULL, 2, '2026-09-25 13:02:15', '2026-09-25 13:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `created_at`) VALUES
(1, 'admin', 'Administrator', '2026-09-23 12:18:04'),
(2, 'manager', 'Marketing Manager', '2026-09-23 12:18:04'),
(3, 'telecaller', 'Telecaller', '2026-09-23 12:18:04'),
(4, 'marketing', 'Marketing Executive', '2026-09-23 12:18:04');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`) VALUES
(41, 1, 1),
(38, 1, 2),
(40, 1, 3),
(39, 1, 4),
(21, 1, 5),
(18, 1, 6),
(20, 1, 7),
(19, 1, 8),
(17, 1, 9),
(5, 1, 10),
(4, 1, 11),
(16, 1, 12),
(14, 1, 13),
(15, 1, 14),
(3, 1, 15),
(1, 1, 16),
(2, 1, 17),
(37, 1, 18),
(34, 1, 19),
(36, 1, 20),
(35, 1, 21),
(45, 1, 22),
(42, 1, 23),
(44, 1, 24),
(43, 1, 25),
(9, 1, 26),
(6, 1, 27),
(8, 1, 28),
(7, 1, 29),
(30, 1, 30),
(27, 1, 31),
(29, 1, 32),
(28, 1, 33),
(13, 1, 34),
(10, 1, 35),
(12, 1, 36),
(11, 1, 37),
(31, 1, 38),
(26, 1, 39),
(24, 1, 40),
(25, 1, 41),
(23, 1, 42),
(22, 1, 43),
(33, 1, 44),
(32, 1, 45),
(186, 2, 5),
(187, 2, 6),
(188, 2, 7),
(189, 2, 9),
(176, 2, 10),
(183, 2, 12),
(184, 2, 13),
(185, 2, 14),
(173, 2, 15),
(174, 2, 16),
(175, 2, 17),
(199, 2, 18),
(200, 2, 19),
(201, 2, 20),
(202, 2, 21),
(203, 2, 22),
(204, 2, 23),
(205, 2, 24),
(177, 2, 26),
(178, 2, 27),
(179, 2, 28),
(195, 2, 30),
(196, 2, 31),
(197, 2, 32),
(180, 2, 34),
(181, 2, 35),
(182, 2, 36),
(198, 2, 38),
(192, 2, 39),
(193, 2, 40),
(194, 2, 41),
(190, 2, 42),
(191, 2, 43),
(224, 3, 5),
(225, 3, 6),
(226, 3, 7),
(219, 3, 10),
(220, 3, 11),
(221, 3, 12),
(222, 3, 13),
(223, 3, 14),
(216, 3, 15),
(217, 3, 16),
(218, 3, 17),
(154, 4, 5),
(152, 4, 6),
(153, 4, 7),
(151, 4, 12),
(149, 4, 13),
(150, 4, 14),
(144, 4, 15),
(142, 4, 16),
(143, 4, 17),
(156, 4, 18),
(159, 4, 22),
(157, 4, 23),
(158, 4, 24),
(145, 4, 26),
(148, 4, 34),
(146, 4, 35),
(147, 4, 36),
(155, 4, 38);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `service_name` varchar(200) NOT NULL,
  `service_code` varchar(50) DEFAULT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `service_code`, `department_id`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Cardiac Consultation', 'CARD-CONS', 1, 'Specialist cardiac consultation', 'ACTIVE', '2026-09-29 07:58:02', '2026-09-29 07:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(100) NOT NULL DEFAULT 'General',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `description`, `created_at`, `updated_at`) VALUES
(1, 'hospital_name', '', 'Hospital', 'Primary hospital name', '2026-09-29 08:07:16', '2026-09-29 08:07:16'),
(2, 'hospital_phone', '', 'Hospital', 'Primary hospital contact number', '2026-09-29 08:07:16', '2026-09-29 08:07:16'),
(3, 'hospital_email', '', 'Hospital', 'Primary hospital email address', '2026-09-29 08:07:16', '2026-09-29 08:07:16'),
(4, 'website_url', '', 'Hospital', 'Hospital website URL', '2026-09-29 08:07:16', '2026-09-29 08:07:16'),
(5, 'timezone', 'Asia/Kolkata', 'System', 'System timezone', '2026-09-29 08:07:16', '2026-09-29 08:07:16'),
(6, 'default_currency', 'INR', 'System', 'Default currency used by the system', '2026-09-29 08:07:16', '2026-09-29 08:07:16'),
(7, 'lead_auto_assignment', 'OFF', 'Leads', 'Enable automatic lead assignment', '2026-09-29 08:07:16', '2026-09-29 08:07:16');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` enum('Lead Follow-up','Doctor Visit','Clinic Visit','Corporate Visit','Field Visit','Event Activity','Promotional Activity','Other') NOT NULL DEFAULT 'Other',
  `assigned_to` int(10) UNSIGNED NOT NULL,
  `related_lead_id` int(10) UNSIGNED DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `status` enum('Pending','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `task_type`, `assigned_to`, `related_lead_id`, `due_date`, `priority`, `status`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Visit Apollo Clinic', 'Meet the clinic coordinator and discuss hospital referral opportunities.', 'Clinic Visit', 3, 3, '2026-09-29 14:00:00', 'Medium', 'Completed', 'everything is good', 2, '2026-09-28 07:35:27', '2026-09-28 10:17:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$f2INrIDiGtn6EoNO1i81Burin4Hrk1jCrSKYFIMUfCFJ2qArIx.9e', 1, 'active', '2026-09-23 12:22:23', '2026-09-23 12:22:23'),
(2, 'Shourya', 'shourya@gmail.com', '$2y$10$vLMuJjPKWNZC.gbJF9UZBOZNOZJo4NijpWocpVM1yZioxSj82NbqC', 2, 'active', '2026-09-24 05:53:15', '2026-09-24 05:53:15'),
(3, 'Vijay', 'vijay@gmail.com', '$2y$10$fXXcORBEvmSTfDyrHmpMPOykomAoyJJszBCOrt9e.rqew8vcXZh5q', 4, 'active', '2026-09-24 06:00:12', '2026-09-24 06:00:12'),
(4, 'Rajesh Sharma', 'rajesh.sharma@example.com', '$2y$10$39dBb6lW936FejQD8A2UIO5ylI2jccBQZnaZeSFy/yz.LW1esYS5u', 3, 'active', '2026-09-24 06:00:51', '2026-09-24 06:00:51');

-- --------------------------------------------------------

--
-- Table structure for table `visit_feedback`
--

CREATE TABLE `visit_feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `visit_id` int(10) UNSIGNED NOT NULL,
  `relationship_status` enum('New Contact','Positive','Interested','Follow-up Required','Referral Potential','Not Interested','Inactive') NOT NULL DEFAULT 'New Contact',
  `feedback` text DEFAULT NULL,
  `next_action` varchar(200) DEFAULT NULL,
  `next_action_at` datetime DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appointments_lead` (`lead_id`),
  ADD KEY `fk_appointments_user` (`created_by`);

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_campaigns_creadted_by` (`created_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`),
  ADD UNIQUE KEY `department_code` (`department_code`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_doctors_department` (`department_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_events_created_by` (`created_by`);

--
-- Indexes for table `event_assignments`
--
ALTER TABLE `event_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_staff` (`event_id`,`user_id`),
  ADD KEY `fk_event_assignments_user` (`user_id`),
  ADD KEY `fk_event_assignments_assigned_by` (`assigned_by`);

--
-- Indexes for table `event_leads`
--
ALTER TABLE `event_leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_lead` (`event_id`,`lead_id`),
  ADD KEY `fk_event_leads_lead` (`lead_id`),
  ADD KEY `fk_event_leads_captured_by` (`captured_by`);

--
-- Indexes for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_leads_source` (`source_id`),
  ADD KEY `fk_leads_assigned_to` (`assigned_to`),
  ADD KEY `fk_leads_created_by` (`created_by`),
  ADD KEY `fk_leads_campaign` (`campaign_id`),
  ADD KEY `fk_leads_referral` (`referral_id`);

--
-- Indexes for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lead_activities_lead` (`lead_id`),
  ADD KEY `fk_lead_activities_user` (`user_id`);

--
-- Indexes for table `lead_calls`
--
ALTER TABLE `lead_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lead_calls_lead` (`lead_id`),
  ADD KEY `fk_lead_calls_user` (`user_id`);

--
-- Indexes for table `marketing_plans`
--
ALTER TABLE `marketing_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_marketing_plans_created_by` (`created_by`);

--
-- Indexes for table `marketing_sources`
--
ALTER TABLE `marketing_sources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `marketing_visits`
--
ALTER TABLE `marketing_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_marketing_visits_assigned_to` (`assigned_to`),
  ADD KEY `fk_marketing_visits_created_by` (`created_by`),
  ADD KEY `fk_marketing_visits_task` (`related_task_id`),
  ADD KEY `fk_marketing_visits_lead` (`related_lead_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_referrals_created_by` (`created_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_code` (`service_code`),
  ADD KEY `fk_services_department` (`department_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tasks_assigned_to` (`assigned_to`),
  ADD KEY `fk_tasks_created_by` (`created_by`),
  ADD KEY `fk_tasks_related_lead` (`related_lead_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- Indexes for table `visit_feedback`
--
ALTER TABLE `visit_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visit_feedback_visit` (`visit_id`),
  ADD KEY `fk_visit_feedback_user` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_assignments`
--
ALTER TABLE `event_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_leads`
--
ALTER TABLE `event_leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lead_activities`
--
ALTER TABLE `lead_activities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `lead_calls`
--
ALTER TABLE `lead_calls`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketing_plans`
--
ALTER TABLE `marketing_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketing_sources`
--
ALTER TABLE `marketing_sources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `marketing_visits`
--
ALTER TABLE `marketing_visits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=227;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visit_feedback`
--
ALTER TABLE `visit_feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `fk_campaigns_creadted_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `fk_doctors_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `event_assignments`
--
ALTER TABLE `event_assignments`
  ADD CONSTRAINT `fk_event_assignments_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_event_assignments_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_event_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `event_leads`
--
ALTER TABLE `event_leads`
  ADD CONSTRAINT `fk_event_leads_captured_by` FOREIGN KEY (`captured_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_event_leads_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_event_leads_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `fk_leads_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leads_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leads_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leads_referral` FOREIGN KEY (`referral_id`) REFERENCES `referrals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leads_source` FOREIGN KEY (`source_id`) REFERENCES `marketing_sources` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD CONSTRAINT `fk_lead_activities_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lead_activities_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `lead_calls`
--
ALTER TABLE `lead_calls`
  ADD CONSTRAINT `fk_lead_calls_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lead_calls_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `marketing_plans`
--
ALTER TABLE `marketing_plans`
  ADD CONSTRAINT `fk_marketing_plans_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `marketing_visits`
--
ALTER TABLE `marketing_visits`
  ADD CONSTRAINT `fk_marketing_visits_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_marketing_visits_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_marketing_visits_lead` FOREIGN KEY (`related_lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_marketing_visits_task` FOREIGN KEY (`related_task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `fk_referrals_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tasks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tasks_related_lead` FOREIGN KEY (`related_lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `visit_feedback`
--
ALTER TABLE `visit_feedback`
  ADD CONSTRAINT `fk_visit_feedback_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_visit_feedback_visit` FOREIGN KEY (`visit_id`) REFERENCES `marketing_visits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
