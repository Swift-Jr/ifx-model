-- phpMyAdmin SQL Dump
-- version 4.7.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 05, 2018 at 08:27 AM
-- Server version: 10.1.32-MariaDB
-- PHP Version: 5.6.30

START TRANSACTION;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `infizime_product`
--

-- --------------------------------------------------------

--
-- Table structure for table `ifx_job`
--

CREATE TABLE `ifx_job` (
  `job_id` int(11) NOT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `process_id` varchar(6) COLLATE utf8_bin DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_bin NOT NULL,
  `params` varchar(2000) COLLATE utf8_bin DEFAULT NULL,
  `queue` varchar(20) COLLATE utf8_bin NOT NULL,
  `status` int(11) NOT NULL,
  `run_after` int(11) NOT NULL,
  `queued_time` int(11) DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT '0',
  `start_time` int(11) DEFAULT NULL,
  `end_time` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `ifx_scheduler_history`
--

CREATE TABLE `ifx_scheduler_history` (
  `ifx_scheduler_history_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `process_id` varchar(6) COLLATE utf8_bin DEFAULT NULL,
  `message` varchar(500) COLLATE utf8_bin NOT NULL,
  `additional_data` mediumblob
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `ifx_worker`
--

CREATE TABLE `ifx_worker` (
  `worker_id` int(11) NOT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_bin NOT NULL,
  `queuename` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `enabled` int(11) NOT NULL DEFAULT '0',
  `process_id` varchar(6) COLLATE utf8_bin DEFAULT NULL,
  `start_time` int(11) DEFAULT NULL,
  `memory_usage` int(11) DEFAULT NULL,
  `job_retry_time` int(11) NOT NULL DEFAULT '60',
  `maximum_job_time` int(11) NOT NULL DEFAULT '60',
  `wait_no_job_available` int(11) NOT NULL DEFAULT '5',
  `wait_between_jobs_starting` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ifx_job`
--
ALTER TABLE `ifx_job`
  ADD PRIMARY KEY (`job_id`),
  ADD UNIQUE KEY `job_id` (`job_id`),
  ADD KEY `queue` (`queue`),
  ADD KEY `status` (`status`),
  ADD KEY `run_after` (`run_after`);

--
-- Indexes for table `ifx_scheduler_history`
--
ALTER TABLE `ifx_scheduler_history`
  ADD PRIMARY KEY (`ifx_scheduler_history_id`),
  ADD UNIQUE KEY `ifx_scheduler_history_id` (`ifx_scheduler_history_id`),
  ADD KEY `type` (`type`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `ifx_worker`
--
ALTER TABLE `ifx_worker`
  ADD PRIMARY KEY (`worker_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ifx_job`
--
ALTER TABLE `ifx_job`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ifx_scheduler_history`
--
ALTER TABLE `ifx_scheduler_history`
  MODIFY `ifx_scheduler_history_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ifx_worker`
--
ALTER TABLE `ifx_worker`
  MODIFY `worker_id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
