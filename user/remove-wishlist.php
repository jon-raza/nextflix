<?php
require_once '../config/database.php';
require_once '../classes/Wishlist.php';
require_once '../includes/auth-check.php';

if (isset($_GET['movie_id'])) {
    $movie_id = $_GET['movie_id'];
    $user_id = $_SESSION['user_id'];
    
    $wishlist = new Wishlist($pdo);
    
    if ($wishlist->removeFromWishlist($user_id, $movie_id)) {
        $_SESSION['success'] = "Movie removed from wishlist!";
    } else {
        $_SESSION['error'] = "Failed to remove from wishlist.";
    }
    
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'wishlist.php'));
    exit();
}
?>