-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2023 at 12:26 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `homeshef`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminsettings`
--

CREATE TABLE `adminsettings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `default_comm` decimal(8,2) DEFAULT 0.00,
  `refugee_comm` decimal(8,2) DEFAULT 0.00,
  `singlemom_comm` decimal(8,2) DEFAULT 0.00,
  `lostjob_comm` decimal(8,2) DEFAULT 0.00,
  `student_comm` decimal(8,2) DEFAULT 0.00,
  `food_default_comm` decimal(8,2) DEFAULT 0.00,
  `radius` decimal(8,2) NOT NULL DEFAULT 1.00 COMMENT 'shef find with in this range to customer',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chefs`
--

CREATE TABLE `chefs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `date_of_birth` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `sub_type` varchar(255) DEFAULT NULL,
  `address_line1` text DEFAULT NULL,
  `address_line2` text DEFAULT NULL,
  `state` text DEFAULT NULL,
  `city` text DEFAULT NULL,
  `postal_code` varchar(255) NOT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `is_mobile_verified` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - not verified 1 - verified',
  `email` varchar(255) NOT NULL,
  `is_email_verified` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - not verified 1 - verified',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_personal_detail_complete` varchar(255) NOT NULL DEFAULT '0' COMMENT '0 - incomplete, 1 - complete',
  `address_proof` varchar(255) DEFAULT NULL,
  `address_proof_path` varchar(255) DEFAULT NULL,
  `id_proof_path1` varchar(255) DEFAULT NULL,
  `id_proof_path2` varchar(255) DEFAULT NULL,
  `are_you_a` varchar(255) DEFAULT NULL COMMENT 'student/refugee/single mom/lost job',
  `are_you_a_file_path` varchar(255) DEFAULT NULL,
  `twitter_link` varchar(255) DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `tiktok_link` varchar(255) DEFAULT NULL,
  `kitchen_name` varchar(255) DEFAULT NULL,
  `kitchen_image_path` varchar(255) DEFAULT NULL,
  `kitchen_types` text DEFAULT NULL,
  `other_kitchen_types` text DEFAULT NULL,
  `about_kitchen` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `transit_number` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `institution_number` varchar(255) DEFAULT NULL,
  `new_to_canada` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 - Yes, 0 - No',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1-Active,0-Inactive,2-Inreview',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chefs`
--

INSERT INTO `chefs` (`id`, `first_name`, `last_name`, `date_of_birth`, `type`, `sub_type`, `address_line1`, `address_line2`, `state`, `city`, `postal_code`, `latitude`, `longitude`, `mobile`, `profile_pic`, `is_mobile_verified`, `email`, `is_email_verified`, `email_verified_at`, `password`, `is_personal_detail_complete`, `address_proof`, `address_proof_path`, `id_proof_path1`, `id_proof_path2`, `are_you_a`, `are_you_a_file_path`, `twitter_link`, `facebook_link`, `tiktok_link`, `kitchen_name`, `kitchen_image_path`, `kitchen_types`, `other_kitchen_types`, `about_kitchen`, `bank_name`, `transit_number`, `account_number`, `institution_number`, `new_to_canada`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(3, 'Ravindra', 'Maurya', '2001-11-04', '1', '1', 'Dasdasdasdas', NULL, NULL, NULL, 'J7A1A4', '45.6182', '-73.79724', '9730423102', 'http://127.0.0.1:8000/storage/chef/1769202906698195.jpg', 0, 'ravindramaurya214@gmail.com', 0, NULL, '$2y$10$/sxeclvvAnnUeppbNZHhqesCeakoJW49vJCdwz6274Ce6P604o.7i', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, '2023-06-20 00:33:13', '2023-06-20 01:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `state_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1-active , 0-inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1-active 0 -inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_item_fields`
--

CREATE TABLE `document_item_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_item_list_id` bigint(20) UNSIGNED DEFAULT NULL,
  `field_name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `mandatory` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0- not required , 1- required',
  `allows_as_kitchen_name` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0- not applicable , 1- applicable',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_item_lists`
--

CREATE TABLE `document_item_lists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `state_id` bigint(20) UNSIGNED DEFAULT NULL,
  `document_item_name` varchar(255) NOT NULL,
  `chef_type` varchar(255) NOT NULL,
  `reference_links` varchar(255) DEFAULT NULL,
  `additional_links` varchar(255) DEFAULT NULL,
  `detail_information` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '0' COMMENT '0- inactive 1- active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kitchentypes`
--

CREATE TABLE `kitchentypes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kitchentype` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1-active 0-inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kitchentypes`
--

INSERT INTO `kitchentypes` (`id`, `kitchentype`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'caribbean', 'http://127.0.0.1:8000/storage/admin/kitchentype/caribbean.png', 1, '2020-06-23 00:36:12', '2020-06-23 00:36:12'),
(2, 'american', 'http://127.0.0.1:8000/storage/admin/kitchentype/american.png', 1, '2020-06-23 00:36:12', '2020-06-23 00:36:12'),
(3, 'pakistani', 'http://127.0.0.1:8000/storage/admin/kitchentype/pakistani.png', 1, '2020-06-23 00:36:13', '2020-06-23 00:36:13'),
(4, 'latin american', 'http://127.0.0.1:8000/storage/admin/kitchentype/latinamerican.png', 1, '2020-06-23 00:36:14', '2020-06-23 00:36:14'),
(5, 'southeast asian', 'http://127.0.0.1:8000/storage/admin/kitchentype/southeastasian.png', 1, '2020-06-23 00:36:15', '2020-06-23 00:36:15'),
(6, 'southern', 'http://127.0.0.1:8000/storage/admin/kitchentype/southern.png', 1, '2020-06-23 00:36:15', '2020-06-23 00:36:15'),
(7, 'mediterranean', 'http://127.0.0.1:8000/storage/admin/kitchentype/mediterranean.png', 1, '2020-06-23 00:36:16', '2020-06-23 00:36:16'),
(8, 'chinese', 'http://127.0.0.1:8000/storage/admin/kitchentype/chinese.png', 1, '2020-06-23 00:36:17', '2020-06-23 00:36:17'),
(9, 'italian', 'http://127.0.0.1:8000/storage/admin/kitchentype/italian.png', 1, '2020-06-23 00:36:17', '2020-06-23 00:36:17'),
(10, 'african', 'http://127.0.0.1:8000/storage/admin/kitchentype/african.png', 1, '2020-06-23 00:36:18', '2020-06-23 00:36:18'),
(11, 'japanese', 'http://127.0.0.1:8000/storage/admin/kitchentype/japanese.png', 1, '2020-06-23 00:36:19', '2020-06-23 00:36:19'),
(12, 'middle eastern', 'http://127.0.0.1:8000/storage/admin/kitchentype/middleeastern.png', 1, '2020-06-23 00:36:20', '2020-06-23 00:36:20'),
(13, 'korean', 'http://127.0.0.1:8000/storage/admin/kitchentype/korean.png', 1, '2020-06-23 00:36:21', '2020-06-23 00:36:21'),
(14, 'mexican', 'http://127.0.0.1:8000/storage/admin/kitchentype/mexican.png', 1, '2020-06-23 00:36:21', '2020-06-23 00:36:21'),
(15, 'indian', 'http://127.0.0.1:8000/storage/admin/kitchentype/indian.png', 1, '2020-06-23 00:36:24', '2020-06-23 00:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2023_06_14_111556_create_chefs_table', 1),
(6, '2023_06_15_094358_create_countries_table', 1),
(7, '2023_06_15_094406_create_states_table', 1),
(8, '2023_06_15_094414_create_cities_table', 1),
(9, '2023_06_15_094422_create_pincodes_table', 1),
(10, '2023_06_16_093756_create_adminsettings_table', 1),
(11, '2023_06_17_062624_create_document_item_lists_table', 1),
(12, '2023_06_17_063135_create_document_item_fields_table', 1),
(13, '2023_06_17_102423_create_taxes_table', 1),
(14, '2023_06_17_103453_create_kitchentypes_table', 1),
(15, '2023_06_17_123039_create_shef_types_table', 1),
(16, '2023_06_17_123112_create_shef_subtypes_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pincodes`
--

CREATE TABLE `pincodes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `city_id` bigint(20) UNSIGNED NOT NULL,
  `pincode` varchar(255) NOT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1-active, 0-inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shef_subtypes`
--

CREATE TABLE `shef_subtypes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '1' COMMENT '0- inactive, 1-active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shef_subtypes`
--

INSERT INTO `shef_subtypes` (`id`, `type_id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Professional', '1', '2019-06-23 04:36:51', '2019-06-23 04:36:51');

-- --------------------------------------------------------

--
-- Table structure for table `shef_types`
--

CREATE TABLE `shef_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '1' COMMENT '0- inactive, 1-active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shef_types`
--

INSERT INTO `shef_types` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Individual', '1', '2023-06-19 05:19:41', '2023-06-19 05:19:41');

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1-active , 0-inactive',
  `tax_type` varchar(255) DEFAULT NULL,
  `tax_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taxes`
--

CREATE TABLE `taxes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tax_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `social_id` varchar(255) DEFAULT NULL,
  `social_type` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 - active, 0 - inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `mobile`, `email`, `password`, `social_id`, `social_type`, `email_verified_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ravindra Maurya', '9730423102', 'ravindramaurya214@gmail.com', '$2y$10$/gWGow2/M5RvU3f4w0Cx6OTcJDOi.9hjV6GekiNXQOq9EzfADL0My', NULL, NULL, NULL, 1, '2023-06-19 04:25:46', '2023-06-19 04:25:46'),
(2, 'ravi mandal', '7977149050', 'ravi@gmail.com', '$2y$10$zAdRdJ9dwSDy48VYbolJbOTG2rzvyZpvEFn7/l18R3aELDWPeiyuG', NULL, NULL, NULL, 1, '2023-06-19 07:57:58', '2023-06-19 07:57:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adminsettings`
--
ALTER TABLE `adminsettings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chefs`
--
ALTER TABLE `chefs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chefs_mobile_unique` (`mobile`),
  ADD UNIQUE KEY `chefs_email_unique` (`email`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cities_state_id_foreign` (`state_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_name_unique` (`name`);

--
-- Indexes for table `document_item_fields`
--
ALTER TABLE `document_item_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_item_fields_document_item_list_id_foreign` (`document_item_list_id`);

--
-- Indexes for table `document_item_lists`
--
ALTER TABLE `document_item_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_item_lists_state_id_foreign` (`state_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `kitchentypes`
--
ALTER TABLE `kitchentypes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `pincodes`
--
ALTER TABLE `pincodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pincodes_pincode_unique` (`pincode`),
  ADD UNIQUE KEY `pincodes_latitude_unique` (`latitude`),
  ADD UNIQUE KEY `pincodes_longitude_unique` (`longitude`),
  ADD KEY `pincodes_city_id_foreign` (`city_id`);

--
-- Indexes for table `shef_subtypes`
--
ALTER TABLE `shef_subtypes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shef_subtypes_type_id_foreign` (`type_id`);

--
-- Indexes for table `shef_types`
--
ALTER TABLE `shef_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `states_country_id_foreign` (`country_id`);

--
-- Indexes for table `taxes`
--
ALTER TABLE `taxes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_mobile_unique` (`mobile`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adminsettings`
--
ALTER TABLE `adminsettings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chefs`
--
ALTER TABLE `chefs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_item_fields`
--
ALTER TABLE `document_item_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_item_lists`
--
ALTER TABLE `document_item_lists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kitchentypes`
--
ALTER TABLE `kitchentypes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pincodes`
--
ALTER TABLE `pincodes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shef_subtypes`
--
ALTER TABLE `shef_subtypes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shef_types`
--
ALTER TABLE `shef_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taxes`
--
ALTER TABLE `taxes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_item_fields`
--
ALTER TABLE `document_item_fields`
  ADD CONSTRAINT `document_item_fields_document_item_list_id_foreign` FOREIGN KEY (`document_item_list_id`) REFERENCES `document_item_lists` (`id`) ON DELETE NO ACTION;

--
-- Constraints for table `document_item_lists`
--
ALTER TABLE `document_item_lists`
  ADD CONSTRAINT `document_item_lists_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE NO ACTION;

--
-- Constraints for table `pincodes`
--
ALTER TABLE `pincodes`
  ADD CONSTRAINT `pincodes_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shef_subtypes`
--
ALTER TABLE `shef_subtypes`
  ADD CONSTRAINT `shef_subtypes_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `shef_types` (`id`) ON DELETE NO ACTION;

--
-- Constraints for table `states`
--
ALTER TABLE `states`
  ADD CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
