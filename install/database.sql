-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 01, 2024 at 06:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_category`
--

CREATE TABLE `{prefix}_category` (
  `type` varchar(20) NOT NULL,
  `category_id` varchar(10) DEFAULT '0',
  `topic` varchar(150) NOT NULL,
  `color` varchar(16) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `{prefix}_category`
--

INSERT INTO `{prefix}_category` (`type`, `category_id`, `topic`, `color`, `published`) VALUES
('department', 1, 'บริหาร', NULL, 1),
('department', 2, 'จัดซื้อจัดจ้าง', NULL, 1),
('department', 3, 'บุคคล', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_language`
--

CREATE TABLE `{prefix}_language` (
  `id` int(11) NOT NULL,
  `key` text NOT NULL,
  `type` varchar(5) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `js` tinyint(1) NOT NULL,
  `th` text DEFAULT NULL,
  `en` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_leave`
--

CREATE TABLE `{prefix}_leave` (
  `id` int(11) NOT NULL,
  `topic` varchar(150) NOT NULL,
  `detail` text NOT NULL,
  `num_days` tinyint(4) NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `{prefix}_leave`
--

INSERT INTO `{prefix}_leave` (`id`, `topic`, `detail`, `num_days`, `published`) VALUES
(1, 'ลาป่วย', 'พนักงานที่ประสงค์จะลาหยุดงาน จะต้องกรอกข้อมูลการลาในระบบ และยื่นขออนุญาตเป็นการล่วงหน้าต่อผู้บังคับบัญชาตามลำดับขั้น และจะหยุดได้ก็ต่อเมื่อผู้บังคับบัญชาได้อนุมัติการลาแล้วเท่านั้น\r\nการลาป่วยต้องแนบใบรับรองแพทย์เสมอ\r\nสามารถลาย้อนหลังได้', 15, 1),
(2, 'ลากิจส่วนตัว', 'พนักงานที่ประสงค์จะลาหยุดงาน จะต้องกรอกข้อมูลการลาในระบบ และยื่นขออนุญาตเป็นการล่วงหน้าต่อผู้บังคับบัญชาตามลำดับขั้น และจะหยุดได้ก็ต่อเมื่อผู้บังคับบัญชาได้อนุมัติการลาแล้วเท่านั้น', 10, 1),
(3, 'ลาคลอดบุตร', 'พนักงานที่ประสงค์จะลาหยุดงาน จะต้องกรอกข้อมูลการลาในระบบ และยื่นขออนุญาตเป็นการล่วงหน้าต่อผู้บังคับบัญชาตามลำดับขั้น และจะหยุดได้ก็ต่อเมื่อผู้บังคับบัญชาได้อนุมัติการลาแล้วเท่านั้น', 60, 1),
(4, 'ลาไปช่วยเหลือภรรยาที่คลอดบุตร', 'พนักงานที่ประสงค์จะลาหยุดงาน จะต้องกรอกข้อมูลการลาในระบบ และยื่นขออนุญาตเป็นการล่วงหน้าต่อผู้บังคับบัญชาตามลำดับขั้น และจะหยุดได้ก็ต่อเมื่อผู้บังคับบัญชาได้อนุมัติการลาแล้วเท่านั้น', 30, 1),
(5, 'ลาเข้ารับการตรวจเลือกทหารหรือเข้ารับการเตรียมพล', 'พนักงานที่ประสงค์จะลาหยุดงาน จะต้องกรอกข้อมูลการลาในระบบ และยื่นขออนุญาตเป็นการล่วงหน้าต่อผู้บังคับบัญชาตามลำดับขั้น และจะหยุดได้ก็ต่อเมื่อผู้บังคับบัญชาได้อนุมัติการลาแล้วเท่านั้น', 10, 1),
(6, 'ลาไปศึกษา ฝึกอบรม ปฏิบัติการวิจัย หรือดูงาน', 'พนักงานที่ประสงค์จะลาหยุดงาน จะต้องกรอกข้อมูลการลาในระบบ และยื่นขออนุญาตเป็นการล่วงหน้าต่อผู้บังคับบัญชาตามลำดับขั้น และจะหยุดได้ก็ต่อเมื่อผู้บังคับบัญชาได้อนุมัติการลาแล้วเท่านั้น', 5, 1),
(8, 'ลาพักผ่อน', '**', 30, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_leave_items`
--

CREATE TABLE `{prefix}_leave_items` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `leave_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `approve` tinyint(1) NOT NULL,
  `closed` tinyint(1) DEFAULT NULL,
  `department` varchar(10) DEFAULT NULL,
  `detail` text NOT NULL,
  `communication` text NOT NULL,
  `start_date` date NOT NULL,
  `start_period` tinyint(1) NOT NULL,
  `end_date` date NOT NULL,
  `end_period` tinyint(1) NOT NULL,
  `create_date` datetime NOT NULL,
  `days` float NOT NULL DEFAULT 0,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_logs`
--

CREATE TABLE `{prefix}_logs` (
  `id` int(11) NOT NULL,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `create_date` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `topic` text NOT NULL,
  `datas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user`
--

CREATE TABLE `{prefix}_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `salt` varchar(32) NOT NULL,
  `password` varchar(50) NOT NULL,
  `token` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `permission` text NOT NULL,
  `name` varchar(150) NOT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `provinceID` varchar(3) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'TH',
  `create_date` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `social` tinyint(1) DEFAULT 0,
  `line_uid` varchar(33) DEFAULT NULL,
  `activatecode` varchar(32) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `{prefix}_user`
--

INSERT INTO `{prefix}_user` (`id`, `username`, `salt`, `password`, `token`, `status`, `permission`, `name`, `sex`, `id_card`, `address`, `phone`, `provinceID`, `province`, `zipcode`, `country`, `create_date`, `active`, `social`, `line_uid`, `activatecode`) VALUES
(3, 'test1', '2261f70cac53b', 'db213b53e1cfb55c4fe7394c8b6b7ee308c203bf', '5663cb3814def92e378ea76d882f3f94d4c83359', 0, '', 'test1', 'u', NULL, '', NULL, '', '', '', 'TH', '2023-12-15 00:50:03', 1, 0, NULL, ''),
(4, 'head1', '5b1d085ed03ca', '0e7580535a909c899da1089a889d1ca6c2968698', 'f8ada9c8e3b427d2115618326494e58997e00fcd', 2, ',can_approve_eleave,', 'head1', 'u', NULL, '', NULL, '', '', '', 'TH', '2023-12-15 00:54:12', 1, 0, NULL, ''),
(5, 'head2', 'd1b57d99cabb8', 'f1a0f268aae4ea2f641560cf1be0f0a6e2b1ca06', '154c0915565db24e2a94a4cd9ee8425f11bae604', 2, ',can_approve_eleave,', 'head2', 'u', NULL, '', NULL, '', '', '', 'TH', '2023-12-15 01:06:19', 1, 0, NULL, ''),
(6, 'head3', 'e633b8e0e25f5', 'e2b95fe5163f800db1c6a66708b1e056286a76d5', 'a7ee5d1b4bf9a734547db41f25c53a35e605d2de', 2, '', 'หัวหน้าแผนก บริหาร', 'u', NULL, '', NULL, '', '', '', 'TH', '2023-12-15 01:16:07', 1, 0, NULL, ''),
(8, 'test3', 'bc72bc2dc9620', '6c8a8ac93db4c4610b2e47eb933c2fb2832d2c48', '070607f3f328a1f12b1370f31e80389fd9e64b94', 0, '', 'พนักงาน บริหาร', 'u', NULL, '', NULL, '', '', '', 'TH', '2023-12-15 01:22:49', 1, 0, NULL, ''),
(9, 'test2', '674aae5fa2097', 'ccd3566b0c8145efce84b9a0114cdc752eecbbad', '088668afde3306e81d5cfa65ed96e40fcea354eb', 0, '', 'พนักงาน จัดซื้อจัดจ้าง', 'u', NULL, '', NULL, '', '', '', 'TH', '2023-12-15 21:06:47', 1, 0, NULL, ''),
(10, 'md', '74456ba46538c', 'd5260892c0276b6ffac244bc3c9af13a75077070', '5daee4c9d2a4232c05fbfe68584356b66da0e477', 3, '', 'ผู้จัดการ อนุมัติหัวหน้าแผนก', 'u', NULL, '', NULL, '', '', '', 'TH', '2023-12-13 15:47:43', 1, 0, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user_meta`
--

CREATE TABLE `{prefix}_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `{prefix}_user_meta`
--

INSERT INTO `{prefix}_user_meta` (`value`, `name`, `member_id`) VALUES
('2', 'department', 7),
('3', 'department', 3),
('2', 'department', 5),
('3', 'department', 4),
('1', 'department', 6),
('1', 'department', 8),
('2', 'department', 9),
('1', 'department', 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `{prefix}_category`
--
ALTER TABLE `{prefix}_category`
  ADD KEY `type` (`type`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_leave`
--
ALTER TABLE `{prefix}_leave`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_leave_items`
--
ALTER TABLE `{prefix}_leave_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `src_id` (`src_id`),
  ADD KEY `module` (`module`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `line_uid` (`line_uid`),
  ADD KEY `username` (`username`),
  ADD KEY `token` (`token`),
  ADD KEY `phone` (`phone`),
  ADD KEY `id_card` (`id_card`),
  ADD KEY `activatecode` (`activatecode`);

--
-- Indexes for table `{prefix}_user_meta`
--
ALTER TABLE `{prefix}_user_meta`
  ADD KEY `member_id` (`member_id`,`name`);

--
-- AUTO_INCREMENT for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_leave`
--
ALTER TABLE `{prefix}_leave`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_leave_items`
--
ALTER TABLE `{prefix}_leave_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
