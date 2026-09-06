-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 06, 2026 lúc 07:19 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `phone_store`
--
CREATE DATABASE IF NOT EXISTS `phone_store` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `phone_store`;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo_url`) VALUES
(1, 'Apple', 'apple', 'assets/images/brands/1788712009-100db766.png'),
(2, 'Samsung', 'samsung', 'assets/images/brands/1788713617-cdc52891.jpg'),
(3, 'Xiaomi', 'xiaomi', 'assets/images/brands/1788713667-e7415739.png'),
(4, 'OPPO', 'oppo', 'assets/images/brands/1788713507-34a39813.png'),
(5, 'Vivo', 'vivo', 'assets/images/brands/1788713639-2a9d2c88.png');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `receiver_name` varchar(150) NOT NULL,
  `receiver_phone` varchar(20) NOT NULL,
  `receiver_address` text NOT NULL,
  `total_amount` decimal(15,0) NOT NULL,
  `payment_method` enum('cod','banking') DEFAULT 'cod',
  `status` enum('pending','confirmed','shipping','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `receiver_name`, `receiver_phone`, `receiver_address`, `total_amount`, `payment_method`, `status`, `created_at`) VALUES
(1, 1, 'Phạm Quang Huy', '0388913500', 'Thanh Xuân, Hà Nội', 29990000, 'cod', 'confirmed', '2026-09-05 19:48:57'),
(2, 1, 'Phạm Quang Huy', '0388913500', 'Hà Nội,45', 9990000, 'cod', 'pending', '2026-09-06 21:37:08'),
(3, 4, 'Minh', '0981289345', '122', 8490000, 'cod', 'shipping', '2026-09-06 23:58:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `variant_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 1, 29990000),
(2, 2, 13, 1, 9990000),
(3, 3, 11, 1, 8490000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `brand_id`, `name`, `slug`, `description`, `thumbnail`, `is_active`) VALUES
(1, 1, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Điện thoại cao cấp của Apple, thiết kế khung titan, hiệu năng mạnh và camera chất lượng cao.', 'assets/images/products/1788707091-17cdfa35.webp', 1),
(2, 1, 'iPhone 15', 'iphone-15', 'iPhone 15 có thiết kế hiện đại, camera chính 48MP và hiệu năng ổn định.', 'assets/images/products/1788707044-3b759c2e.jpg', 1),
(3, 2, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Flagship cao cấp của Samsung với màn hình lớn, camera độ phân giải cao và bút S Pen.', 'assets/images/products/1788707011-80d92e63.webp', 1),
(4, 2, 'Samsung Galaxy A55 5G', 'samsung-galaxy-a55-5g', 'Điện thoại tầm trung Samsung với màn hình AMOLED, pin lớn và hỗ trợ 5G.', 'assets/images/products/1788706920-2b5e8c89.webp', 1),
(5, 3, 'Xiaomi 14', 'xiaomi-14', 'Điện thoại Xiaomi cao cấp với cấu hình mạnh, camera chất lượng và sạc nhanh.', 'assets/images/products/1788706872-176999f0.webp', 1),
(6, 3, 'Redmi Note 13 Pro 5G', 'redmi-note-13-pro-5g', 'Mẫu điện thoại tầm trung nổi bật với camera độ phân giải cao và pin dung lượng lớn.', 'assets/images/products/1788705552-e5e82e04.jpg', 1),
(7, 4, 'OPPO Reno11 5G', 'oppo-reno11-5g', 'Điện thoại OPPO dòng Reno với thiết kế đẹp, camera chân dung và sạc nhanh.', 'assets/images/products/1788617309-bb9c1b6e.jpg', 1),
(8, 5, 'Vivo V30 5G', 'vivo-v30-5g', 'Điện thoại Vivo thiết kế mỏng nhẹ, camera tốt và pin dung lượng cao.', 'assets/images/products/1788617197-e74cdc64.png', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_specs`
--

CREATE TABLE `product_specs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `screen_size` varchar(50) DEFAULT NULL,
  `screen_tech` varchar(150) DEFAULT NULL,
  `refresh_rate` int(11) DEFAULT NULL,
  `chipset` varchar(150) DEFAULT NULL,
  `gpu` varchar(150) DEFAULT NULL,
  `ram` int(11) DEFAULT NULL,
  `camera_rear` varchar(255) DEFAULT NULL,
  `camera_front` varchar(100) DEFAULT NULL,
  `battery_capacity` int(11) DEFAULT NULL,
  `charging_speed` int(11) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `sim` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_specs`
--

INSERT INTO `product_specs` (`id`, `product_id`, `screen_size`, `screen_tech`, `refresh_rate`, `chipset`, `gpu`, `ram`, `camera_rear`, `camera_front`, `battery_capacity`, `charging_speed`, `os`, `sim`) VALUES
(1, 1, '6.7 inch', 'OLED', 120, 'Apple A17 Pro', 'Apple GPU', 8, '48MP + 12MP + 12MP', '12MP', 4422, 20, 'iOS 17', 'Nano-SIM + eSIM'),
(2, 2, '6.1 inch', 'OLED', 60, 'Apple A16 Bionic', 'Apple GPU', 6, '48MP + 12MP', '12MP', 3349, 20, 'iOS 17', 'Nano-SIM + eSIM'),
(3, 3, '6.8 inch', 'Dynamic AMOLED 2X', 120, 'Snapdragon 8 Gen 3', 'Adreno 750', 12, '200MP + 50MP + 12MP + 10MP', '12MP', 5000, 45, 'Android 14', '2 Nano-SIM + eSIM'),
(4, 4, '6.6 inch', 'Super AMOLED', 120, 'Exynos 1480', 'Xclipse 530', 8, '50MP + 12MP + 5MP', '32MP', 5000, 25, 'Android 14', '2 Nano-SIM'),
(5, 5, '6.36 inch', 'LTPO OLED', 120, 'Snapdragon 8 Gen 3', 'Adreno 750', 12, '50MP + 50MP + 50MP', '32MP', 4610, 90, 'Android 14', '2 Nano-SIM'),
(6, 6, '6.67 inch', 'AMOLED', 120, 'Snapdragon 7s Gen 2', 'Adreno 710', 8, '200MP + 8MP + 2MP', '16MP', 5100, 67, 'Android 13', '2 Nano-SIM'),
(7, 7, '6.7 inch', 'AMOLED', 120, 'MediaTek Dimensity 7050', 'Mali-G68', 8, '50MP + 32MP + 8MP', '32MP', 5000, 67, 'Android 14', '2 Nano-SIM'),
(8, 8, '6.78 inch', 'AMOLED', 120, 'Snapdragon 7 Gen 3', 'Adreno 720', 12, '50MP + 50MP', '50MP', 5000, 80, 'Android 14', '2 Nano-SIM');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(100) NOT NULL,
  `storage` varchar(50) NOT NULL,
  `price` decimal(15,0) NOT NULL,
  `sale_price` decimal(15,0) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `variant_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `color`, `storage`, `price`, `sale_price`, `stock_quantity`, `variant_image`) VALUES
(1, 1, 'Titan Tự Nhiên', '256GB', 34990000, 29990000, 10, 'assets/images/variants/1788713453-c40a7f8e.jpg'),
(2, 1, 'Titan Đen', '512GB', 40990000, 35990000, 6, 'assets/images/variants/1788713428-d6cca0ae.jpg'),
(3, 2, 'Đen', '128GB', 22990000, 19990000, 15, 'assets/images/variants/1788713387-ef52fdf4.jpg'),
(4, 2, 'Hồng', '256GB', 25990000, 22990000, 9, 'assets/images/variants/1788713381-aaccb026.jpg'),
(5, 3, 'Đen Titan', '256GB', 33990000, 28990000, 12, 'assets/images/variants/1788713324-430742b8.webp'),
(6, 3, 'Xám Titan', '512GB', 37990000, 32990000, 8, 'assets/images/variants/1788713317-dd88e098.webp'),
(7, 4, 'Xanh', '128GB', 9990000, 8990000, 20, 'assets/images/variants/1788713242-c8f34fd2.jpg'),
(8, 4, 'Đen', '256GB', 10990000, 9990000, 17, 'assets/images/variants/1788713249-828d961e.webp'),
(9, 5, 'Đen', '256GB', 22990000, 19990000, 11, 'assets/images/variants/1788713184-837639e1.webp'),
(10, 5, 'Trắng', '512GB', 25990000, 22990000, 7, 'assets/images/variants/1788713178-85e3c19d.png'),
(11, 6, 'Đen', '256GB', 9490000, 8490000, 24, 'assets/images/variants/1788713105-5c581847.png'),
(12, 6, 'Tím', '512GB', 10990000, 9490000, 13, 'assets/images/variants/1788713098-8a74649b.jpg'),
(13, 7, 'Xanh', '256GB', 10990000, 9990000, 17, 'assets/images/variants/1788713051-68c73a48.jpg'),
(14, 7, 'Xám', '256GB', 10990000, 9990000, 14, 'assets/images/variants/1788713045-2c3ecebc.jpg'),
(15, 8, 'Xanh Biển', '256GB', 13990000, 12990000, 16, 'assets/images/variants/1788713002-66ef0fe0.jpg'),
(16, 8, 'Đen', '512GB', 15990000, 14990000, 10, 'assets/images/variants/1788712993-669d05a8.png');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `address`, `role`) VALUES
(1, 'Phạm Quang Huy', 'huy@gmail.com', '0409', '0388913500', 'Hà Nội', 'customer'),
(2, 'Lê Quảng An', 'an@gmail.com', '123456', '0912345123', 'Hà Nội', 'staff'),
(3, 'admin', 'admin@gmail.com', '123', '0912345678', 'Hà Nội', 'admin'),
(4, 'Minh', 'minh@example.com', '$2y$10$47g1OnuN/N3xseRfvgOnSeih3GfTM48Fcof.Nxq1tzNg20/fQerhS', '0981289345', NULL, 'customer');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Chỉ mục cho bảng `product_specs`
--
ALTER TABLE `product_specs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `product_specs`
--
ALTER TABLE `product_specs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`);

--
-- Các ràng buộc cho bảng `product_specs`
--
ALTER TABLE `product_specs`
  ADD CONSTRAINT `product_specs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Các ràng buộc cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
