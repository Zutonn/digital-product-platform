-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2025 at 11:43 AM
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
-- Database: `db_users`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(14, 35, 3, 1, '2025-11-13 08:32:20');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `delivery_method` varchar(50) DEFAULT 'license_code',
  `license_code` text DEFAULT NULL,
  `status` enum('pending','processing','paid','completed','cancelled') DEFAULT 'processing',
  `payment_proof` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `product_id`, `order_number`, `product_name`, `price`, `delivery_method`, `license_code`, `status`, `payment_proof`, `created_at`) VALUES
(8, 33, 1, 'ORD-20251112-99D898', 'Spotify Premium 3 Bulan', 59000.00, 'license_code', NULL, 'completed', NULL, '2025-11-12 18:32:27'),
(9, 33, 3, 'ORD-20251112-EA340B', 'Youtube Family 1 Bulan', 10000.00, 'license_code', NULL, 'completed', NULL, '2025-11-12 18:32:27'),
(10, 33, 1, 'ORD-20251112-90B8C6', 'Spotify Premium 3 Bulan', 59000.00, 'license_code', NULL, 'completed', NULL, '2025-11-12 19:00:31'),
(11, 34, 3, 'ORD-20251112-99C4E4', 'Youtube Family 1 Bulan', 10000.00, 'license_code', NULL, 'cancelled', NULL, '2025-11-12 20:31:06');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `delivery_method` enum('license_code','topup','account','download_link') DEFAULT 'license_code',
  `platform` varchar(100) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `category`, `price`, `sale_price`, `delivery_method`, `platform`, `region`, `stock`, `is_active`, `description`, `image`, `created_at`) VALUES
(1, 'ZPD-SPOT-3M', 'Spotify Premium 3 Bulan', 'Premium Apps', 59000.00, NULL, 'license_code', 'Global', 'Global', 3, 1, 'Langganan Spotify 3 bulan', 'uploads/products/product_1762889029_69138d45b0f26.png', '2025-11-09 14:24:19'),
(3, '', 'Youtube Family 1 Bulan', 'Premium Apps', 10000.00, NULL, 'license_code', 'Android/iOS/Desktop', 'Global/ID', 19, 1, '', 'uploads/products/product_1762977528_6914e6f88d61f.png', '2025-11-12 03:25:59');

-- --------------------------------------------------------

--
-- Table structure for table `product_codes`
--

CREATE TABLE `product_codes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(19, 'admin', NULL, '$2y$10$yWklHK6qmPUheTovnqi0j.JW3G8SS.IngRP0KLs0lSAtRjRS/Sbau', 'admin_utama'),
(31, 'user', 'user@tokodigital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(33, 'asep', 'asep@gmail.com', '$2y$10$r6PvNeZQ.LtaDoOT87BgkuOSY9Y7KjSoYqPnb1DsrjUmHiGBMTMXW', 'user'),
(34, 'faqih', 'faqih@gmail.com', '$2y$10$5nT.fQ4MfjR9dZm0ASLFYu.jTSXm0jud2kMWi5C2ibaV6zD.f9ksi', 'user'),
(35, 'biawak', 'biawak@x.com', '$2y$10$XCCquyBKMNBtwx6iGhwITO5zcXBjpNTGDqreyinzbbk8tJFqLGib.', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `product_codes`
--
ALTER TABLE `product_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_codes`
--
ALTER TABLE `product_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product_codes`
--
ALTER TABLE `product_codes`
  ADD CONSTRAINT `product_codes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
