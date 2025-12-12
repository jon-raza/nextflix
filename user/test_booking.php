<?php
session_start();
require_once '../config/database.php';

echo "<h2>Booking System Test</h2>";

// Check current user
echo "<h3>Current User:</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not logged in') . "<br>";
echo "Username: " . ($_SESSION['username'] ?? 'Not logged in') . "<br>";

// Check bookings table
echo "<h3>Bookings Table:</h3>";
$bookings = $pdo->query("SELECT * FROM bookings")->fetchAll();
echo "Total bookings: " . count($bookings) . "<br>";

if(count($bookings) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Show ID</th><th>Seats</th><th>Status</th></tr>";
    foreach($bookings as $b) {
        echo "<tr>";
        echo "<td>{$b['id']}</td>";
        echo "<td>{$b['user_id']}</td>";
        echo "<td>{$b['show_id']}</td>";
        echo "<td>{$b['seats_booked']}</td>";
        echo "<td>{$b['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No bookings in database!</p>";
}

// Check if we need to create a test booking
echo "<h3>Create Test Booking:</h3>";
echo "<form method='post'>";
echo "<input type='submit' name='create_test' value='Create Test Booking'>";
echo "</form>";

if(isset($_POST['create_test'])) {
    // Create a test booking
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, show_id, seats_booked, total_amount, status) VALUES (?, 1, 2, 100.00, 'confirmed')");
    if($stmt->execute([$_SESSION['user_id']])) {
        echo "<p style='color: green;'>Test booking created successfully!</p>";
        header("Refresh:2");
        exit();
    }
}
?>