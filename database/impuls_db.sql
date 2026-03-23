-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Хост: sql100.infinityfree.com
-- Время создания: Мар 23 2026 г., 11:13
-- Версия сервера: 11.4.10-MariaDB
-- Версия PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `if0_41356758_impuls`
--

-- --------------------------------------------------------

--
-- Структура таблицы `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(10, 4, 5, 1, '2026-03-17 08:23:17', '2026-03-17 08:23:17'),
(11, 5, 7, 2, '2026-03-17 10:12:09', '2026-03-17 10:16:22');

-- --------------------------------------------------------

--
-- Структура таблицы `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(1, 1, 5, '2026-03-11 08:40:55'),
(2, 1, 1, '2026-03-11 11:38:05'),
(5, 1, 4, '2026-03-12 11:03:06'),
(6, 1, 7, '2026-03-12 11:03:07'),
(7, 4, 4, '2026-03-17 08:20:56'),
(8, 4, 3, '2026-03-17 08:20:57'),
(15, 4, 1, '2026-03-17 08:21:07'),
(16, 4, 5, '2026-03-17 08:23:19'),
(19, 6, 5, '2026-03-17 10:42:04'),
(20, 6, 7, '2026-03-17 10:42:06');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `payment_method` enum('cash','card') DEFAULT 'cash',
  `comment` text DEFAULT NULL,
  `status` enum('new','processing','shipped','delivered','cancelled') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `customer_name`, `customer_phone`, `pickup_date`, `payment_method`, `comment`, `status`, `created_at`) VALUES
(1, 1, '283960.00', 'Никита Романов', '89997643256', '2026-11-11 11:11:00', 'cash', '', 'cancelled', '2026-03-11 09:09:00'),
(2, 1, '59900.00', 'Лучшая девушка на свете', '8 900 605 56 00', '2026-03-12 10:10:00', 'card', 'Обязательно с розовым бантиком', 'processing', '2026-03-11 19:10:56'),
(3, 1, '119800.00', 'Nikita', '8 999 999 99 99', '2026-03-31 16:16:00', 'card', 'Хочу розовый', 'new', '2026-03-12 13:17:08'),
(4, 4, '76990.00', 'fd', '893432104', '2026-03-17 11:23:00', 'card', 'у аа на канал зайди ко мне,\r\nу аа лайк ведет меня к мечте', 'new', '2026-03-17 08:22:45'),
(5, 6, '59900.00', '123', '89308809999', '2026-11-17 13:21:00', 'card', '123', 'new', '2026-03-17 10:21:57');

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`) VALUES
(1, 1, 4, 'iPhone 17 Pro Max 256GB Silver', '123990.00', 2),
(2, 1, 5, 'AirPods 4 White', '17990.00', 2),
(3, 2, 7, 'Apple MacBook Air 13 256GB MGN63 Gray', '59900.00', 1),
(4, 3, 7, 'Apple MacBook Air 13 256GB Gray', '59900.00', 2),
(5, 4, 1, 'iPhone 16 256GB White', '76990.00', 1),
(6, 5, 7, 'Apple MacBook Air 13 256GB Gray', '59900.00', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `rating` decimal(2,1) DEFAULT 5.0,
  `reviews` int(11) DEFAULT 0,
  `badge` varchar(50) DEFAULT NULL,
  `badge_text` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `price`, `old_price`, `image`, `rating`, `reviews`, `badge`, `badge_text`, `description`, `specs`, `category`, `created_at`) VALUES
(1, 'iPhone 16 256GB White', NULL, '76990.00', '88490.00', 'uploads/products/product_69b0536378ef0.png', '5.0', 124, 'Хит', 'Хит', NULL, NULL, 'iphone', '2026-03-10 17:07:28'),
(2, 'iPad 11 A15 128GB Wi-Fi Silver', NULL, '34990.00', '40190.00', 'uploads/products/product_69b05309bab62.png', '4.5', 89, 'sale', '-15%', NULL, NULL, 'ipad', '2026-03-10 17:07:28'),
(3, 'iPhone 17 eSIM 256GB White', NULL, '77990.00', '89690.00', 'uploads/products/product_69b0535d0e517.png', '5.0', 256, 'new', 'New', NULL, NULL, 'iphone', '2026-03-10 17:07:28'),
(4, 'iPhone 17 Pro Max 256GB Silver', NULL, '123990.00', '142590.00', 'uploads/products/product_69b05369a17fe.png', '5.0', 342, NULL, NULL, NULL, NULL, 'iphone', '2026-03-10 17:07:28'),
(5, 'AirPods 4 White', '-ir-ods-4--hite-69b2957253caf', '17990.00', '20690.00', 'uploads/products/product_69b2957253529.png', '4.5', 178, '0', 'Топ', '', NULL, 'airpods', '2026-03-10 17:07:28'),
(7, 'Apple MacBook Air 13 256GB Gray', '-pple--ac-ook--ir-13-256----ray-69b2a9d6b7dff', '59900.00', '68900.00', 'uploads/products/product_69b1a39e6740a.png', '5.0', 100, NULL, '-13%', '', NULL, 'mac', '2026-03-11 17:17:18');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `role`, `created_at`) VALUES
(1, 'romanovns', '$2y$10$PPQ6kM8KgQh3QKCg8M3d8.IfV7AYXXpgd7OD8wKz/d3rs6.QzSeVu', 'buzznrom1@gmail.com', '+7 (900) 601 05 10', 'user', '2026-03-10 17:43:12'),
(2, 'shakhaevata', '$2y$10$CDCFN8tlTWuVDpNiftM6fuy/a/JVqdq8pnTvKCwISw8w5quDCz1g2', '123@123.ru', '9999999999', 'user', '2026-03-10 18:01:02'),
(4, 'tf', '$2y$10$fH8FFu0zIermwOfHvxdxx.KGF.WTVOk0yZx/fLvUZiHPVLuG6H4qa', 'hjd@tg.com', '89306765544', 'user', '2026-03-17 08:20:03'),
(5, 'вып', '$2y$10$XpTlT5NGy.tRwVI65aMiK.LguVKbVvSm4f9IT8QSW3V/rkZmjtNYO', 'sadf@gmail.com', '324342423324', 'user', '2026-03-17 10:06:31'),
(6, '123', '$2y$10$C1EfE/v0vAvbB5OPM7m3mu4BpinkReBiYUPM9/X2s4oo23MQU6L0q', '123@gmail.com', '89308809999', 'user', '2026-03-17 10:17:22'),
(7, 'test', '$2y$10$WAz.r8nXSUpe0ZNiuTtvKuqjYtQXsdYdfhoj6o0YsQT5UjwnxCVzq', 'test@test.test', '8900000000', 'user', '2026-03-17 11:14:22');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
