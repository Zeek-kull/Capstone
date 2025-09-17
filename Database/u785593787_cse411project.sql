-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 17, 2025 at 10:10 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u785593787_cse411project`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `ad_id` int(11) NOT NULL,
  `userid` varchar(100) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`ad_id`, `userid`, `pass`, `created_at`) VALUES
(1, 'admin', 'admin', '2025-08-13 14:05:20');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `c_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `o_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `totalproduct` varchar(100) NOT NULL,
  `totalprice` decimal(10,2) NOT NULL,
  `status` enum('Pending','Packing','Shipped','Completed','Cancelled') DEFAULT 'Pending',
  `status_updated_at` timestamp NULL DEFAULT NULL,
  `transaction_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`o_id`, `user_id`, `name`, `address`, `phone`, `payment_method`, `totalproduct`, `totalprice`, `status`, `status_updated_at`, `transaction_number`, `created_at`) VALUES
(55, 22, 'William Ken', '1329 Zone 6 Cansinala, Apalit, Pampanga', '09270417510', 'COD', '22 (1)', 14.00, 'Shipped', '2025-09-17 21:59:57', '34567890', '2025-09-17 21:37:35.000000');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `os_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) NOT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`os_id`, `order_id`, `old_status`, `new_status`, `changed_by`, `change_reason`, `created_at`) VALUES
(97, 55, 'Pending', 'Packing', 1, '', '2025-09-17 21:59:47'),
(98, 55, 'Packing', 'Shipped', 1, '', '2025-09-17 21:59:57');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `p_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `size` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `imgname` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lens_id` varchar(255) NOT NULL,
  `group_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`p_id`, `name`, `category`, `description`, `tags`, `quantity`, `size`, `price`, `imgname`, `created_at`, `lens_id`, `group_id`) VALUES
(22, 'Asdfghjklljhg', 'Med', 'adsfgh', 'Men', 10, '', 14.00, '480238317_595799980026468_7218712900231802138_n_68c7760e6491e1.24704273.jpg', '2025-09-15 02:12:30', '', ''),
(23, 'Paul Sedrick', 'Med', 'asdfghj', 'Women', 11, '', 12.00, '480313111_595799783359821_7706880964424918838_n_68c776d81b2be0.16442625.jpg', '2025-09-15 02:15:52', '', ''),
(24, 'Masdasd', 'Med', 'asdasdasd', 'Men', 12, '', 33.00, '480245439_595799786693154_410606908606491604_n_68c77af7c438c0.83514393.jpg', '2025-09-15 02:33:27', '850dbd2f-b51f-4845-8df6-df39b09ff486', 'ba676a53-a9e3-442a-b283-4a1537745b00'),
(26, 'Aaaaaaaa', 'Med', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaafdfhasdfghjkl,;.fha fffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 'Men', 2, '', 455.00, '480761308_602459846027148_7687139113083090295_n_68c79ac51d8944.62833842.jpg', '2025-09-15 04:49:09', '73158efa-a275-40a1-ac35-8d765439db5c', 'ba676a53-a9e3-442a-b283-4a1537745b00'),
(27, 'Aaaaaaaa', 'Med', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaafdfhasdfghjkl,;.fha fffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 'Men', 2, '', 455.00, '480696036_595799826693150_3947571533499198911_n_68c79cd9198e09.81377181.jpg', '2025-09-15 04:58:01', '9946edc9-d50e-45a1-980b-694d109781f5', 'ba676a53-a9e3-442a-b283-4a1537745b00'),
(28, 'Multiply Jorts', 'jorts', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaafdfhasdfghjkl,;.fha fffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 'Men', 2, '', 1555.00, 'Multiply_Jorts_68c7ae24cb5a72.32167409.jpg', '2025-09-15 06:11:48', 'ecbb8874-55c3-4b2d-9341-8f004b9eb274', 'ba676a53-a9e3-442a-b283-4a1537745b00');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `s_id` int(11) NOT NULL,
  `s_key` varchar(128) NOT NULL,
  `s_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`s_id`, `s_key`, `s_value`, `created_at`) VALUES
(1, 'shipping_fee', '0.00', '2025-09-17 21:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `u_id` int(11) NOT NULL,
  `f_name` varchar(100) NOT NULL,
  `l_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `zone` varchar(100) DEFAULT NULL,
  `street` varchar(150) NOT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `f_name`, `l_name`, `email`, `phone`, `pass`, `zone`, `street`, `barangay`, `city`, `province`, `region`, `created_at`) VALUES
(22, 'William Ken', 'Emperado', 'wemperado004@gmail.com', '09270417510', '$2y$10$wIUgn7/YseokjWxCnQQoB.8zIqwplOmSmkUf7NAZfyiNP1.ldVg3y', 'Zone 6', '1329', 'Cansinala', 'Apalit', 'Pampanga', 'Region III (Central Luzon)', '2025-09-12 17:43:51'),
(23, 'Adrienne', 'Marco', 'adrienne1@gmail.com', '09611096268', '$2y$10$L9T0Eqr30yrtyd/6nnWvbeHbFDuVmLQbSQfFgfuMq4RUQMtRPzN6O', '', '229', 'Camachile', 'Orion', 'Bataan', 'Region III (Central Luzon)', '2025-09-15 03:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_order_cancellations`
--

CREATE TABLE `user_order_cancellations` (
  `uc_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`ad_id`),
  ADD UNIQUE KEY `userid` (`userid`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`c_id`),
  ADD KEY `idx_cart_user` (`user_id`),
  ADD KEY `idx_cart_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`o_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_transaction_number` (`transaction_number`(100));

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`os_id`),
  ADD KEY `idx_history_order` (`order_id`),
  ADD KEY `idx_history_admin` (`changed_by`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`p_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`s_id`),
  ADD UNIQUE KEY `s_key` (`s_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`u_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_order_cancellations`
--
ALTER TABLE `user_order_cancellations`
  ADD PRIMARY KEY (`uc_id`),
  ADD KEY `idx_uoc_order` (`order_id`),
  ADD KEY `idx_uoc_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `ad_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `o_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `os_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `p_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `s_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_order_cancellations`
--
ALTER TABLE `user_order_cancellations`
  MODIFY `uc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`p_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`o_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `admin` (`ad_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_order_cancellations`
--
ALTER TABLE `user_order_cancellations`
  ADD CONSTRAINT `fk_uoc_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`o_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uoc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
