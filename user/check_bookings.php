<?php
session_start();
require_once '../config/database.php';

echo "<h3>Checking Bookings Status</h3>";

// Check all bookings for current user
$stmt = $pdo->prepare("SELECT id, show_id, status, booking_date FROM bookings WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

foreach($bookings as $booking) {
    echo "Booking ID: {$booking['id']} | Show ID: {$booking['show_id']} | Status: {$booking['status']} | Date: {$booking['booking_date']}<br>";
}

// Check booking process
echo "<h3>Check Booking Process</h3>";
echo "<a href='../user/movie-details.php?id=1' target='_blank'>Test Booking</a>";
?>