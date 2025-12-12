-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 12:35 PM
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
-- Database: `nextflix`
--
CREATE Database nextflix;
use nextflix;

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateRevenueStats` ()   BEGIN
    DECLARE total DECIMAL(12,2);
    DECLARE daily DECIMAL(12,2);
    DECLARE weekly DECIMAL(12,2);
    DECLARE monthly DECIMAL(12,2);
    
    -- Total revenue (confirmed bookings)
    SELECT COALESCE(SUM(total_amount), 0) INTO total 
    FROM bookings 
    WHERE status = 'confirmed';
    
    -- Daily revenue (today's confirmed bookings)
    SELECT COALESCE(SUM(total_amount), 0) INTO daily 
    FROM bookings 
    WHERE status = 'confirmed' AND DATE(booking_date) = CURDATE();
    
    -- Weekly revenue (current week's confirmed bookings)
    SELECT COALESCE(SUM(total_amount), 0) INTO weekly 
    FROM bookings 
    WHERE status = 'confirmed' AND YEARWEEK(booking_date) = YEARWEEK(CURDATE());
    
    -- Monthly revenue (current month's confirmed bookings)
    SELECT COALESCE(SUM(total_amount), 0) INTO monthly 
    FROM bookings 
    WHERE status = 'confirmed' AND YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE());
    
    -- Update revenue stats
    UPDATE revenue_stats 
    SET total_revenue = total,
        daily_revenue = daily,
        weekly_revenue = weekly,
        monthly_revenue = monthly,
        last_updated = NOW();
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT 'admin_default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@nextflix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nextflix Admin', NULL, 'admin_default.jpg', '2025-11-08 19:12:09', '2025-11-09 07:37:19'),
(2, 'admin12', 'testadmin@gmail.com', '$2y$10$7JfbiyAj.Lao9ZybAl0BAuqS/4NujOvHiGy2yFqBt.Gqrx8WNsCTW', 'Test Admin', '03000000000', 'admin_default.jpg', '2025-11-10 19:39:04', '2025-11-10 19:39:04');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `show_id` int(11) NOT NULL,
  `seats_booked` int(11) NOT NULL,
  `selected_seats` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('confirmed','cancelled','completed') DEFAULT 'confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `show_id`, `seats_booked`, `selected_seats`, `total_amount`, `booking_date`, `status`) VALUES
(2, 1, 6, 3, 'A1,A2,A3', 302.97, '2025-11-10 13:00:34', 'completed'),
(3, 3, 7, 2, 'A1,A2,A3', 201.98, '2025-11-10 20:48:51', ''),
(4, 3, 8, 4, 'A1,A2,A3', 403.96, '2025-11-10 22:11:28', ''),
(5, 3, 9, 6, 'A1,A2,A3', 479.94, '2025-11-11 08:03:09', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `cinemas`
--

CREATE TABLE `cinemas` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(500) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cinemas`
--

INSERT INTO `cinemas` (`id`, `name`, `location`, `total_seats`, `created_at`) VALUES
(1, 'Nextflix Cinema Downtown', '123 Main Street, City Center', 100, '2025-11-08 19:12:09'),
(2, 'Nextflix Cinema Mall', '456 Shopping Mall, Mega City', 150, '2025-11-08 19:12:09'),
(3, 'Nextflix Cinema Premium', '789 Luxury Road, Business District', 80, '2025-11-08 19:12:09');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `description` text NOT NULL,
  `genre` varchar(255) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `release_year` year(4) DEFAULT NULL,
  `thumbnail_url` varchar(500) NOT NULL,
  `trailer_embed_code` text NOT NULL,
  `rating` decimal(3,1) DEFAULT 0.0,
  `price` decimal(8,2) DEFAULT 0.00,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `description`, `genre`, `duration`, `release_year`, `thumbnail_url`, `trailer_embed_code`, `rating`, `price`, `is_featured`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'The Dark Knight', 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.', 'Action, Crime, Drama', '152 min', '2008', 'https://img.youtube.com/vi/EXeTwQWrcwY/hqdefault.jpg', '<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/EXeTwQWrcwY?si=63l3Vp5OCxxm5KfO\" title=\"YouTube video player\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" referrerpolicy=\"strict-origin-when-cross-origin\" allowfullscreen></iframe>', 0.0, 50.99, 1, 1, '2025-11-08 19:12:09', '2025-11-09 19:28:05'),
(2, 'Inception', 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.', 'Action, Sci-Fi, Thriller', '148 min', '2010', 'https://img.youtube.com/vi/YoHD9XEInc0/maxresdefault.jpg', '<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/YoHD9XEInc0?si=hZRx-Mv9Jh17nZax\" title=\"YouTube video player\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" referrerpolicy=\"strict-origin-when-cross-origin\" allowfullscreen></iframe>', 0.0, 49.99, 1, 1, '2025-11-08 19:12:09', '2025-11-09 19:29:43'),
(3, 'The Shawshank Redemption', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', 'Drama', '142 min', '1994', 'https://img.youtube.com/vi/PLl99DlL6b4/hqdefault.jpg', '<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/PLl99DlL6b4?si=cwVcyl_Rlb8BvmW8\" title=\"YouTube video player\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" referrerpolicy=\"strict-origin-when-cross-origin\" allowfullscreen></iframe>', 0.0, 79.99, 0, 1, '2025-11-08 19:12:09', '2025-11-09 19:30:59'),
(5, 'Saiyaara', 'A heartwarming tale of love, sacrifice, and destiny. Saiyaara takes you on an emotional journey through the lives of two souls destined to be together against all odds.', 'Romance, Drama, Musical', '148 min', '2024', 'https://img.vwassets.com/oakscenter.net/vertical_8f347a74-106a-4da3-a1e6-468f113b8efe.jpg', '<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/9r-tT5IN0vg\" frameborder=\"0\" allowfullscreen></iframe>', 0.0, 100.99, 1, 1, '2025-11-09 16:06:59', '2025-11-09 16:22:25'),
(6, 'Commando2', 'Commando 2 is an action-packed Bollywood film following a skilled Indian commando on a mission to track down black money launderers. Filled with high-octane stunts, clever twists, and intense combat scenes, he takes on a global conspiracy. It’s a thrilling mix of patriotism, strategy, and relentless action.', 'Action', '150mins', '2024', 'https://c8.alamy.com/comp/HT5PW4/commando-2-poster-vidyut-jamwal-2017-reliance-entertainment-courtesy-HT5PW4.jpg', '<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/gO4ZIgHOR50?si=emeRgN5wf0mdm7gT\" title=\"YouTube video player\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" referrerpolicy=\"strict-origin-when-cross-origin\" allowfullscreen></iframe>', 0.0, 60.39, 1, 1, '2025-11-10 12:10:25', '2025-11-10 21:35:14');

-- --------------------------------------------------------

--
-- Table structure for table `revenue_stats`
--

CREATE TABLE `revenue_stats` (
  `id` int(11) NOT NULL,
  `total_revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `daily_revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `weekly_revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `monthly_revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `revenue_stats`
--

INSERT INTO `revenue_stats` (`id`, `total_revenue`, `daily_revenue`, `weekly_revenue`, `monthly_revenue`, `last_updated`) VALUES
(1, 0.00, 0.00, 0.00, 0.00, '2025-11-11 08:12:28');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `movie_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 5, 'best movie', '2025-11-08 19:29:45', '2025-11-08 19:29:45'),
(2, 2, 2, 2, 'Nice movie', '2025-11-09 12:05:58', '2025-11-09 12:05:58'),
(3, 3, 6, 5, 'best action movie', '2025-11-10 22:10:59', '2025-11-10 22:10:59');

-- --------------------------------------------------------

--
-- Table structure for table `shows`
--

CREATE TABLE `shows` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `cinema_id` int(11) NOT NULL,
  `show_date` date NOT NULL,
  `show_time` time NOT NULL,
  `available_seats` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shows`
--

INSERT INTO `shows` (`id`, `movie_id`, `cinema_id`, `show_date`, `show_time`, `available_seats`, `created_at`) VALUES
(1, 1, 1, '2024-01-20', '14:00:00', 100, '2025-11-08 19:12:09'),
(2, 1, 1, '2024-01-20', '18:00:00', 100, '2025-11-08 19:12:09'),
(3, 2, 2, '2024-01-20', '15:30:00', 150, '2025-11-08 19:12:09'),
(4, 3, 3, '2024-01-20', '16:00:00', 80, '2025-11-08 19:12:09'),
(5, 5, 1, '2024-01-25', '18:00:00', 100, '2025-11-09 16:06:59'),
(6, 5, 2, '2028-04-05', '19:00:00', 97, '2025-11-10 13:00:34'),
(7, 5, 1, '2025-12-12', '19:00:00', 98, '2025-11-10 20:48:51'),
(8, 5, 3, '2025-11-22', '22:00:00', 96, '2025-11-10 22:11:28'),
(9, 3, 3, '2026-12-12', '22:00:00', 94, '2025-11-11 08:03:09');

-- --------------------------------------------------------

--
-- Table structure for table `show_seats`
--

CREATE TABLE `show_seats` (
  `id` int(11) NOT NULL,
  `show_id` int(11) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `show_seats`
--

INSERT INTO `show_seats` (`id`, `show_id`, `seat_number`, `is_available`) VALUES
(1, 1, 'A1', 1),
(2, 1, 'A2', 1),
(3, 1, 'A3', 1),
(4, 1, 'A4', 1),
(5, 1, 'A5', 1),
(6, 1, 'B1', 1),
(7, 1, 'B2', 1),
(8, 1, 'B3', 1),
(9, 1, 'B4', 1),
(10, 1, 'B5', 1),
(11, 2, 'A1', 1),
(12, 2, 'A2', 1),
(13, 2, 'A3', 1),
(14, 2, 'A4', 1),
(15, 2, 'A5', 1),
(16, 3, 'A1', 1),
(17, 3, 'A2', 1),
(18, 3, 'A3', 1),
(19, 3, 'A4', 1),
(20, 3, 'A5', 1),
(21, 4, 'A1', 1),
(22, 4, 'A2', 1),
(23, 4, 'A3', 1),
(24, 4, 'A4', 1),
(25, 4, 'A5', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `profile_picture` varchar(500) DEFAULT 'default.jpg',
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `profile_picture`, `phone`, `date_of_birth`, `created_at`, `updated_at`) VALUES
(1, 'yousuf', 'yousuf@gmail.com', '$2y$10$09cb9JEBJMYzp7VcRcauBeTQ/rRn1LTYWPHvgExOd5XxgvwliohDe', 'Muhammad Yousuf Khan', 'default.jpg', '03214563214', '2020-02-12', '2025-11-08 19:13:52', '2025-11-08 21:29:21'),
(2, 'ali', 'ali@gmail.com', '$2y$10$BIgS3pOhP6bdFsHbrHqGIOEP6Y3VuOKvQ7Yj9swrmLnlc/Df2igB.', 'Muhammad Ali Shah', 'default.jpg', '03000000000', '2020-12-12', '2025-11-09 11:42:22', '2025-11-09 12:44:47'),
(3, 'ahsan', 'ahsan@gmail.com', '$2y$10$9XI0KrgdbH7csR9LW7VoUe.dyykd8dfD15Ccprbr9kBvYulpVUu92', 'Muhammad Ahsan', 'default.jpg', '03000000000', '2022-02-12', '2025-11-10 19:57:48', '2025-11-10 22:08:45');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `movie_id`, `created_at`) VALUES
(1, 3, 5, '2025-11-11 06:53:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `show_id` (`show_id`),
  ADD KEY `idx_booking_user` (`user_id`);

--
-- Indexes for table `cinemas`
--
ALTER TABLE `cinemas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_movie_genre` (`genre`);

--
-- Indexes for table `revenue_stats`
--
ALTER TABLE `revenue_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_movie` (`user_id`,`movie_id`),
  ADD KEY `idx_review_movie` (`movie_id`);

--
-- Indexes for table `shows`
--
ALTER TABLE `shows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `cinema_id` (`cinema_id`),
  ADD KEY `idx_show_date` (`show_date`);

--
-- Indexes for table `show_seats`
--
ALTER TABLE `show_seats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `show_id` (`show_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_movie` (`user_id`,`movie_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cinemas`
--
ALTER TABLE `cinemas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `revenue_stats`
--
ALTER TABLE `revenue_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shows`
--
ALTER TABLE `shows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `show_seats`
--
ALTER TABLE `show_seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`);

--
-- Constraints for table `movies`
--
ALTER TABLE `movies`
  ADD CONSTRAINT `movies_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`);

--
-- Constraints for table `shows`
--
ALTER TABLE `shows`
  ADD CONSTRAINT `shows_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`),
  ADD CONSTRAINT `shows_ibfk_2` FOREIGN KEY (`cinema_id`) REFERENCES `cinemas` (`id`);

--
-- Constraints for table `show_seats`
--
ALTER TABLE `show_seats`
  ADD CONSTRAINT `show_seats_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
