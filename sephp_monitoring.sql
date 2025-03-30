-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2025 at 12:57 AM
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
-- Database: `sephp_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `aws_metrics`
--

CREATE TABLE `aws_metrics` (
  `id` int(11) NOT NULL,
  `instance_id` varchar(255) NOT NULL,
  `cpu_utilization` float DEFAULT NULL,
  `memory_utilization` float DEFAULT NULL,
  `network_in` float DEFAULT NULL,
  `network_out` float DEFAULT NULL,
  `instance_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aws_metrics`
--

INSERT INTO `aws_metrics` (`id`, `instance_id`, `cpu_utilization`, `memory_utilization`, `network_in`, `network_out`, `instance_status`, `created_at`) VALUES
(1, 'i-0cc66966e4187f36b', 3.86667, 0, 0.000195503, 0.000283718, 'healthy', '2025-03-11 05:34:20'),
(2, 'i-0cc66966e4187f36b', 3.86667, 0, 0.000195503, 0.000283718, 'healthy', '2025-03-11 05:34:22'),
(3, 'i-0cc66966e4187f36b', 3.86667, 0, 0.000195503, 0.000283718, 'healthy', '2025-03-11 05:34:25'),
(4, 'i-0cc66966e4187f36b', 3.86667, 0, 0.000195503, 0.000283718, 'healthy', '2025-03-11 05:34:26'),
(5, 'i-0cc66966e4187f36b', 7.42084, 0, 0.802316, 0.00398946, 'healthy', '2025-03-11 05:54:37'),
(6, 'i-0cc66966e4187f36b', 7.42084, 0, 0.802316, 0.00398946, 'healthy', '2025-03-11 05:54:39'),
(7, 'i-0cc66966e4187f36b', 7.42084, 0, 0.641853, 0.00319157, 'healthy', '2025-03-11 05:55:03'),
(8, 'i-0cc66966e4187f36b', 6.74036, 0, 0.641853, 0.00319157, 'healthy', '2025-03-11 05:55:05'),
(9, 'i-0cc66966e4187f36b', 3.85498, 0, 0.0000476837, 0.0000654856, 'healthy', '2025-03-11 05:58:28'),
(10, 'i-0cc66966e4187f36b', 3.85498, 0, 0.0000476837, 0.0000654856, 'healthy', '2025-03-11 05:58:30'),
(11, 'i-0cc66966e4187f36b', 3.85498, 0, 0.0000476837, 0.0000654856, 'healthy', '2025-03-11 05:58:33'),
(12, 'i-0cc66966e4187f36b', 3.85498, 0, 0.0000476837, 0.0000654856, 'healthy', '2025-03-11 05:58:35'),
(13, 'i-0cc66966e4187f36b', 3.85498, 0, 0.0000476837, 0.0000654856, 'healthy', '2025-03-11 05:58:40'),
(14, 'i-0cc66966e4187f36b', 3.85498, 0, 0.0000476837, 0.0000654856, 'healthy', '2025-03-11 05:58:53'),
(15, 'i-0cc66966e4187f36b', 3.85498, 0, 0.0000357628, 0.000104427, 'healthy', '2025-03-11 05:58:55'),
(16, 'i-0cc66966e4187f36b', 3.835, 0, 0.000134468, 0.000448227, 'healthy', '2025-03-12 19:57:09'),
(17, 'i-0cc66966e4187f36b', 3.835, 0, 0.000134468, 0.000448227, 'healthy', '2025-03-12 19:57:11'),
(18, 'i-0cc66966e4187f36b', 3.80083, 0, 0.000134468, 0.000448227, 'healthy', '2025-03-12 19:57:57'),
(19, 'i-0cc66966e4187f36b', 3.80083, 0, 0.000134468, 0.000448227, 'healthy', '2025-03-12 19:57:59'),
(20, 'i-0cc66966e4187f36b', 3.80083, 0, 0.000134468, 0.000448227, 'healthy', '2025-03-12 19:58:01'),
(21, 'i-0cc66966e4187f36b', 3.82085, 0, 0.000188351, 0.000447273, 'healthy', '2025-03-12 19:59:14'),
(22, 'i-0cc66966e4187f36b', 3.82085, 0, 0.000188351, 0.000447273, 'healthy', '2025-03-12 19:59:16'),
(23, 'i-0cc66966e4187f36b', 3.85332, 0, 0.000162125, 0.000368118, 'healthy', '2025-03-12 20:00:33'),
(24, 'i-0cc66966e4187f36b', 3.85332, 0, 0.000162125, 0.000368118, 'healthy', '2025-03-12 20:00:35'),
(25, 'i-0cc66966e4187f36b', 3.86667, 0, 0.000170708, 0.000174522, 'healthy', '2025-03-12 20:02:07'),
(26, 'i-0cc66966e4187f36b', 3.86667, 0, 0.000170708, 0.000174522, 'healthy', '2025-03-12 20:02:09'),
(27, 'i-0cc66966e4187f36b', 3.86162, 0, 0.000156403, 0.000251134, 'healthy', '2025-03-12 20:03:47'),
(28, 'i-0cc66966e4187f36b', 3.86162, 0, 0.000156403, 0.000251134, 'healthy', '2025-03-12 20:03:49'),
(29, 'i-0cc66966e4187f36b', 3.75834, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:39:40'),
(30, 'i-0cc66966e4187f36b', 3.75834, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:39:42'),
(31, 'i-0cc66966e4187f36b', 3.75834, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:39:44'),
(32, 'i-0cc66966e4187f36b', 3.75834, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:39:46'),
(33, 'i-0cc66966e4187f36b', 3.77667, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:40:03'),
(34, 'i-0cc66966e4187f36b', 3.77667, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:40:04'),
(35, 'i-0cc66966e4187f36b', 3.77667, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:40:07'),
(36, 'i-0cc66966e4187f36b', 3.77667, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:40:09'),
(37, 'i-0cc66966e4187f36b', 3.77667, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:40:24'),
(38, 'i-0cc66966e4187f36b', 3.77667, 0, 0.00100555, 0.00122604, 'healthy', '2025-03-13 07:40:26'),
(39, 'i-0cc66966e4187f36b', 0, 0, 0.00351429, 0.00264931, 'healthy', '2025-03-23 22:36:28'),
(40, 'i-0cc66966e4187f36b', 4.48543, 0, 0.000141144, 0.000317574, 'healthy', '2025-03-23 23:02:07'),
(41, 'i-0cc66966e4187f36b', 4.48543, 0, 0.000141144, 0.000317574, 'healthy', '2025-03-23 23:02:09'),
(42, 'i-0cc66966e4187f36b', 4.48543, 0, 0.000141144, 0.000317574, 'healthy', '2025-03-23 23:02:12'),
(43, 'i-0cc66966e4187f36b', 4.48543, 0, 0.000141144, 0.000317574, 'healthy', '2025-03-23 23:02:14'),
(44, 'i-0cc66966e4187f36b', 4.44444, 0, 0.000084877, 0.000241756, 'healthy', '2025-03-23 23:03:47'),
(45, 'i-0cc66966e4187f36b', 4.44444, 0, 0.000084877, 0.000241756, 'healthy', '2025-03-23 23:03:48'),
(46, 'i-0cc66966e4187f36b', 4.4547, 0, 0.000084877, 0.000241756, 'healthy', '2025-03-23 23:04:01'),
(47, 'i-0cc66966e4187f36b', 4.4547, 0, 0.000084877, 0.000241756, 'healthy', '2025-03-23 23:04:04'),
(48, 'i-0cc66966e4187f36b', 4.4547, 0, 0.000084877, 0.000241756, 'healthy', '2025-03-23 23:04:08'),
(49, 'i-0cc66966e4187f36b', 4.4547, 0, 0.000084877, 0.000241756, 'healthy', '2025-03-23 23:04:10'),
(50, 'i-0cc66966e4187f36b', 0, 0, 0.000314713, 0.00030899, 'healthy', '2025-03-23 23:11:10'),
(51, 'i-0cc66966e4187f36b', 0, 0, 0.000314713, 0.00030899, 'healthy', '2025-03-23 23:11:12'),
(52, 'i-0cc66966e4187f36b', 4.55, 0, 0.000162888, 0.000154495, 'healthy', '2025-03-23 23:15:34'),
(53, 'i-0cc66966e4187f36b', 4.55, 0, 0.000162888, 0.000154495, 'healthy', '2025-03-23 23:15:36');

-- --------------------------------------------------------

--
-- Table structure for table `connections`
--

CREATE TABLE `connections` (
  `id` int(11) NOT NULL,
  `connection_id` varchar(255) NOT NULL,
  `connected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_ping` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deployments`
--

CREATE TABLE `deployments` (
  `id` int(11) NOT NULL,
  `repository` varchar(255) NOT NULL,
  `environment` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL,
  `commit_sha` varchar(40) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deployments`
--

INSERT INTO `deployments` (`id`, `repository`, `environment`, `status`, `commit_sha`, `description`, `created_at`, `updated_at`) VALUES
(1, 'main-app', 'production', 'success', '8f34dc21a3b9a0b4d669218d0f3fe5f0d2ef4c4a', 'Sprint 45 release', '2025-03-11 05:33:32', '2025-03-11 05:33:32'),
(2, 'api-service', 'staging', 'running', 'a7d3fcb91a3cb89d214fe52b8c8af3f9a234bd98', 'New API endpoints', '2025-03-11 05:33:32', '2025-03-11 05:33:32'),
(3, 'web-client', 'development', 'failed', 'c6d91fe528b4a1deb9742990f2c5192bc9a84b11', 'Frontend update', '2025-03-11 05:33:32', '2025-03-11 05:33:32');

-- --------------------------------------------------------

--
-- Table structure for table `github_events`
--

CREATE TABLE `github_events` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `repository` varchar(255) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `commit_count` int(11) DEFAULT 0,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `github_events`
--

INSERT INTO `github_events` (`id`, `event_type`, `repository`, `branch`, `author`, `commit_count`, `details`, `created_at`) VALUES
(1, 'push', 'testing', 'main', 'challaruthvik', 1, '{\"ref\":\"refs\\/heads\\/main\",\"before\":\"70dd6e2e10eb4553db3525a2d6dce8e2d2fa2148\",\"after\":\"59878ae043142633af4bfa5a08da555e31211c55\",\"repository\":{\"id\":934329240,\"node_id\":\"R_kgDON7C7mA\",\"name\":\"testing\",\"full_name\":\"challaruthvik\\/testing\",\"private\":false,\"owner\":{\"name\":\"challaruthvik\",\"email\":\"challaruthvik@gmail.com\",\"login\":\"challaruthvik\",\"id\":15624884,\"node_id\":\"MDQ6VXNlcjE1NjI0ODg0\",\"avatar_url\":\"https:\\/\\/avatars.githubusercontent.com\\/u\\/15624884?v=4\",\"gravatar_id\":\"\",\"url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\",\"html_url\":\"https:\\/\\/github.com\\/challaruthvik\",\"followers_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/followers\",\"following_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/following{\\/other_user}\",\"gists_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/gists{\\/gist_id}\",\"starred_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/starred{\\/owner}{\\/repo}\",\"subscriptions_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/subscriptions\",\"organizations_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/orgs\",\"repos_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/repos\",\"events_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/events{\\/privacy}\",\"received_events_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/received_events\",\"type\":\"User\",\"user_view_type\":\"public\",\"site_admin\":false},\"html_url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\",\"description\":null,\"fork\":false,\"url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\",\"forks_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/forks\",\"keys_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/keys{\\/key_id}\",\"collaborators_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/collaborators{\\/collaborator}\",\"teams_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/teams\",\"hooks_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/hooks\",\"issue_events_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/issues\\/events{\\/number}\",\"events_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/events\",\"assignees_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/assignees{\\/user}\",\"branches_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/branches{\\/branch}\",\"tags_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/tags\",\"blobs_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/blobs{\\/sha}\",\"git_tags_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/tags{\\/sha}\",\"git_refs_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/refs{\\/sha}\",\"trees_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/trees{\\/sha}\",\"statuses_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/statuses\\/{sha}\",\"languages_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/languages\",\"stargazers_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/stargazers\",\"contributors_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/contributors\",\"subscribers_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/subscribers\",\"subscription_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/subscription\",\"commits_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/commits{\\/sha}\",\"git_commits_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/commits{\\/sha}\",\"comments_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/comments{\\/number}\",\"issue_comment_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/issues\\/comments{\\/number}\",\"contents_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/contents\\/{+path}\",\"compare_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/compare\\/{base}...{head}\",\"merges_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/merges\",\"archive_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/{archive_format}{\\/ref}\",\"downloads_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/downloads\",\"issues_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/issues{\\/number}\",\"pulls_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/pulls{\\/number}\",\"milestones_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/milestones{\\/number}\",\"notifications_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/notifications{?since,all,participating}\",\"labels_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/labels{\\/name}\",\"releases_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/releases{\\/id}\",\"deployments_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/deployments\",\"created_at\":1739810522,\"updated_at\":\"2025-02-17T16:58:16Z\",\"pushed_at\":1742772766,\"git_url\":\"git:\\/\\/github.com\\/challaruthvik\\/testing.git\",\"ssh_url\":\"git@github.com:challaruthvik\\/testing.git\",\"clone_url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing.git\",\"svn_url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\",\"homepage\":null,\"size\":1,\"stargazers_count\":0,\"watchers_count\":0,\"language\":\"HTML\",\"has_issues\":true,\"has_projects\":true,\"has_downloads\":true,\"has_wiki\":true,\"has_pages\":false,\"has_discussions\":false,\"forks_count\":0,\"mirror_url\":null,\"archived\":false,\"disabled\":false,\"open_issues_count\":0,\"license\":null,\"allow_forking\":true,\"is_template\":false,\"web_commit_signoff_required\":false,\"topics\":[],\"visibility\":\"public\",\"forks\":0,\"open_issues\":0,\"watchers\":0,\"default_branch\":\"main\",\"stargazers\":0,\"master_branch\":\"main\"},\"pusher\":{\"name\":\"challaruthvik\",\"email\":\"challaruthvik@gmail.com\"},\"sender\":{\"login\":\"challaruthvik\",\"id\":15624884,\"node_id\":\"MDQ6VXNlcjE1NjI0ODg0\",\"avatar_url\":\"https:\\/\\/avatars.githubusercontent.com\\/u\\/15624884?v=4\",\"gravatar_id\":\"\",\"url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\",\"html_url\":\"https:\\/\\/github.com\\/challaruthvik\",\"followers_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/followers\",\"following_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/following{\\/other_user}\",\"gists_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/gists{\\/gist_id}\",\"starred_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/starred{\\/owner}{\\/repo}\",\"subscriptions_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/subscriptions\",\"organizations_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/orgs\",\"repos_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/repos\",\"events_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/events{\\/privacy}\",\"received_events_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/received_events\",\"type\":\"User\",\"user_view_type\":\"public\",\"site_admin\":false},\"created\":false,\"deleted\":false,\"forced\":false,\"base_ref\":null,\"compare\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\\/compare\\/70dd6e2e10eb...59878ae04314\",\"commits\":[{\"id\":\"59878ae043142633af4bfa5a08da555e31211c55\",\"tree_id\":\"20788c2c3ccf91c31848be53bee211002b61d737\",\"distinct\":true,\"message\":\"Create ss\",\"timestamp\":\"2025-03-24T05:02:46+05:30\",\"url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\\/commit\\/59878ae043142633af4bfa5a08da555e31211c55\",\"author\":{\"name\":\"Ruthvik Challa\",\"email\":\"challaruthvik@gmail.com\",\"username\":\"challaruthvik\"},\"committer\":{\"name\":\"GitHub\",\"email\":\"noreply@github.com\",\"username\":\"web-flow\"},\"added\":[\"ss\"],\"removed\":[],\"modified\":[]}],\"head_commit\":{\"id\":\"59878ae043142633af4bfa5a08da555e31211c55\",\"tree_id\":\"20788c2c3ccf91c31848be53bee211002b61d737\",\"distinct\":true,\"message\":\"Create ss\",\"timestamp\":\"2025-03-24T05:02:46+05:30\",\"url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\\/commit\\/59878ae043142633af4bfa5a08da555e31211c55\",\"author\":{\"name\":\"Ruthvik Challa\",\"email\":\"challaruthvik@gmail.com\",\"username\":\"challaruthvik\"},\"committer\":{\"name\":\"GitHub\",\"email\":\"noreply@github.com\",\"username\":\"web-flow\"},\"added\":[\"ss\"],\"removed\":[],\"modified\":[]}}', '2025-03-23 23:32:37'),
(2, 'push', 'testing', 'main', 'challaruthvik', 1, '{\"ref\":\"refs\\/heads\\/main\",\"before\":\"59878ae043142633af4bfa5a08da555e31211c55\",\"after\":\"b25b9d378b647e556db4b0c4913d01a631599b54\",\"repository\":{\"id\":934329240,\"node_id\":\"R_kgDON7C7mA\",\"name\":\"testing\",\"full_name\":\"challaruthvik\\/testing\",\"private\":false,\"owner\":{\"name\":\"challaruthvik\",\"email\":\"challaruthvik@gmail.com\",\"login\":\"challaruthvik\",\"id\":15624884,\"node_id\":\"MDQ6VXNlcjE1NjI0ODg0\",\"avatar_url\":\"https:\\/\\/avatars.githubusercontent.com\\/u\\/15624884?v=4\",\"gravatar_id\":\"\",\"url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\",\"html_url\":\"https:\\/\\/github.com\\/challaruthvik\",\"followers_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/followers\",\"following_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/following{\\/other_user}\",\"gists_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/gists{\\/gist_id}\",\"starred_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/starred{\\/owner}{\\/repo}\",\"subscriptions_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/subscriptions\",\"organizations_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/orgs\",\"repos_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/repos\",\"events_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/events{\\/privacy}\",\"received_events_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/received_events\",\"type\":\"User\",\"user_view_type\":\"public\",\"site_admin\":false},\"html_url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\",\"description\":null,\"fork\":false,\"url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\",\"forks_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/forks\",\"keys_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/keys{\\/key_id}\",\"collaborators_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/collaborators{\\/collaborator}\",\"teams_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/teams\",\"hooks_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/hooks\",\"issue_events_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/issues\\/events{\\/number}\",\"events_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/events\",\"assignees_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/assignees{\\/user}\",\"branches_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/branches{\\/branch}\",\"tags_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/tags\",\"blobs_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/blobs{\\/sha}\",\"git_tags_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/tags{\\/sha}\",\"git_refs_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/refs{\\/sha}\",\"trees_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/trees{\\/sha}\",\"statuses_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/statuses\\/{sha}\",\"languages_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/languages\",\"stargazers_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/stargazers\",\"contributors_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/contributors\",\"subscribers_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/subscribers\",\"subscription_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/subscription\",\"commits_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/commits{\\/sha}\",\"git_commits_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/git\\/commits{\\/sha}\",\"comments_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/comments{\\/number}\",\"issue_comment_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/issues\\/comments{\\/number}\",\"contents_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/contents\\/{+path}\",\"compare_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/compare\\/{base}...{head}\",\"merges_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/merges\",\"archive_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/{archive_format}{\\/ref}\",\"downloads_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/downloads\",\"issues_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/issues{\\/number}\",\"pulls_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/pulls{\\/number}\",\"milestones_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/milestones{\\/number}\",\"notifications_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/notifications{?since,all,participating}\",\"labels_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/labels{\\/name}\",\"releases_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/releases{\\/id}\",\"deployments_url\":\"https:\\/\\/api.github.com\\/repos\\/challaruthvik\\/testing\\/deployments\",\"created_at\":1739810522,\"updated_at\":\"2025-03-23T23:32:50Z\",\"pushed_at\":1742772974,\"git_url\":\"git:\\/\\/github.com\\/challaruthvik\\/testing.git\",\"ssh_url\":\"git@github.com:challaruthvik\\/testing.git\",\"clone_url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing.git\",\"svn_url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\",\"homepage\":null,\"size\":1,\"stargazers_count\":0,\"watchers_count\":0,\"language\":\"HTML\",\"has_issues\":true,\"has_projects\":true,\"has_downloads\":true,\"has_wiki\":true,\"has_pages\":false,\"has_discussions\":false,\"forks_count\":0,\"mirror_url\":null,\"archived\":false,\"disabled\":false,\"open_issues_count\":0,\"license\":null,\"allow_forking\":true,\"is_template\":false,\"web_commit_signoff_required\":false,\"topics\":[],\"visibility\":\"public\",\"forks\":0,\"open_issues\":0,\"watchers\":0,\"default_branch\":\"main\",\"stargazers\":0,\"master_branch\":\"main\"},\"pusher\":{\"name\":\"challaruthvik\",\"email\":\"challaruthvik@gmail.com\"},\"sender\":{\"login\":\"challaruthvik\",\"id\":15624884,\"node_id\":\"MDQ6VXNlcjE1NjI0ODg0\",\"avatar_url\":\"https:\\/\\/avatars.githubusercontent.com\\/u\\/15624884?v=4\",\"gravatar_id\":\"\",\"url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\",\"html_url\":\"https:\\/\\/github.com\\/challaruthvik\",\"followers_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/followers\",\"following_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/following{\\/other_user}\",\"gists_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/gists{\\/gist_id}\",\"starred_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/starred{\\/owner}{\\/repo}\",\"subscriptions_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/subscriptions\",\"organizations_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/orgs\",\"repos_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/repos\",\"events_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/events{\\/privacy}\",\"received_events_url\":\"https:\\/\\/api.github.com\\/users\\/challaruthvik\\/received_events\",\"type\":\"User\",\"user_view_type\":\"public\",\"site_admin\":false},\"created\":false,\"deleted\":false,\"forced\":false,\"base_ref\":null,\"compare\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\\/compare\\/59878ae04314...b25b9d378b64\",\"commits\":[{\"id\":\"b25b9d378b647e556db4b0c4913d01a631599b54\",\"tree_id\":\"daf5c493292a216037b428d4ade26c71615a6c28\",\"distinct\":true,\"message\":\"Update ss\",\"timestamp\":\"2025-03-24T05:06:14+05:30\",\"url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\\/commit\\/b25b9d378b647e556db4b0c4913d01a631599b54\",\"author\":{\"name\":\"Ruthvik Challa\",\"email\":\"challaruthvik@gmail.com\",\"username\":\"challaruthvik\"},\"committer\":{\"name\":\"GitHub\",\"email\":\"noreply@github.com\",\"username\":\"web-flow\"},\"added\":[],\"removed\":[],\"modified\":[\"ss\"]}],\"head_commit\":{\"id\":\"b25b9d378b647e556db4b0c4913d01a631599b54\",\"tree_id\":\"daf5c493292a216037b428d4ade26c71615a6c28\",\"distinct\":true,\"message\":\"Update ss\",\"timestamp\":\"2025-03-24T05:06:14+05:30\",\"url\":\"https:\\/\\/github.com\\/challaruthvik\\/testing\\/commit\\/b25b9d378b647e556db4b0c4913d01a631599b54\",\"author\":{\"name\":\"Ruthvik Challa\",\"email\":\"challaruthvik@gmail.com\",\"username\":\"challaruthvik\"},\"committer\":{\"name\":\"GitHub\",\"email\":\"noreply@github.com\",\"username\":\"web-flow\"},\"added\":[],\"removed\":[],\"modified\":[\"ss\"]}}', '2025-03-23 23:36:04');

-- --------------------------------------------------------

--
-- Table structure for table `metrics`
--

CREATE TABLE `metrics` (
  `id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `metric_name` varchar(255) NOT NULL,
  `metric_value` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'unknown',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aws_metrics`
--
ALTER TABLE `aws_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instance_time` (`instance_id`,`created_at`);

--
-- Indexes for table `connections`
--
ALTER TABLE `connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `connection_id` (`connection_id`);

--
-- Indexes for table `deployments`
--
ALTER TABLE `deployments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `github_events`
--
ALTER TABLE `github_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `metrics`
--
ALTER TABLE `metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aws_metrics`
--
ALTER TABLE `aws_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `connections`
--
ALTER TABLE `connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deployments`
--
ALTER TABLE `deployments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `github_events`
--
ALTER TABLE `github_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `metrics`
--
ALTER TABLE `metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `metrics`
--
ALTER TABLE `metrics`
  ADD CONSTRAINT `metrics_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
