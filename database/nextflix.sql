SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- TABLE: admins
-- --------------------------------------------------------

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@nextflix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nextflix Admin', NULL, 'admin_default.jpg', '2025-11-08 19:12:09', '2025-11-09 07:37:19'),
(2, 'admin12', 'testadmin@gmail.com', '$2y$10$7JfbiyAj.Lao9ZybAl0BAuqS/4NujOvHiGy2yFqBt.Gqrx8WNsCTW', 'Test Admin', '03000000000', 'admin_default.jpg', '2025-11-10 19:39:04', '2025-11-10 19:39:04');

-- --------------------------------------------------------
-- TABLE: bookings
-- --------------------------------------------------------

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `show_id` int(11) NOT NULL,
  `seats_booked` int(11) NOT NULL,
  `selected_seats` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('confirmed','cancelled','completed') DEFAULT 'confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `bookings` (`id`, `user_id`, `show_id`, `seats_booked`, `selected_seats`, `total_amount`, `booking_date`, `status`) VALUES
(2, 1, 6, 3, 'A1,A2,A3', 302.97, '2025-11-10 13:00:34', 'completed');

-- --------------------------------------------------------
-- TABLE: cinemas
-- --------------------------------------------------------

CREATE TABLE `cinemas` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(500) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cinemas` (`id`, `name`, `location`, `total_seats`, `created_at`) VALUES
(1, 'Nextflix Cinema Downtown', '123 Main Street, City Center', 100, '2025-11-08 19:12:09');

-- --------------------------------------------------------
-- TABLE: movies
-- --------------------------------------------------------

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `genre` varchar(255) NOT NULL,
  `duration` int(11) NOT NULL,
  `rating` decimal(3,1) NOT NULL DEFAULT 0.0,
  `release_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `poster` varchar(500) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Add your movie records here if needed)

-- --------------------------------------------------------
-- TABLE: users
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT 'user_default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `profile_picture`, `created_at`) VALUES
(1, 'John Doe', 'john@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03001234567', 'user_default.jpg', '2025-11-07 10:12:04');

-- --------------------------------------------------------
-- TABLE: reviews
-- --------------------------------------------------------

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- TABLE: shows
-- --------------------------------------------------------

CREATE TABLE `shows` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `cinema_id` int(11) NOT NULL,
  `show_time` datetime NOT NULL,
  `price_per_ticket` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `shows` (`id`, `movie_id`, `cinema_id`, `show_time`, `price_per_ticket`) VALUES
(6, 1, 1, '2025-11-10 16:00:00', 100.99);

-- --------------------------------------------------------
-- TABLE: show_seats
-- --------------------------------------------------------

CREATE TABLE `show_seats` (
  `id` int(11) NOT NULL,
  `show_id` int(11) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `is_booked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- TABLE: wishlist
-- --------------------------------------------------------

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- PRIMARY KEYS & AUTO INCREMENTS
-- --------------------------------------------------------

ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `cinemas`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `shows`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `show_seats`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `cinemas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `shows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `show_seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;
