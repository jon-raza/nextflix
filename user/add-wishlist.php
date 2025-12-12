<?php
require_once '../config/database.php';
require_once '../classes/Wishlist.php';
require_once '../includes/auth-check.php';

if (isset($_GET['movie_id'])) {
    $movie_id = $_GET['movie_id'];
    $user_id = $_SESSION['user_id'];
    
    $wishlist = new Wishlist($pdo);
    
    if (!$wishlist->isInWishlist($user_id, $movie_id)) {
        if ($wishlist->addToWishlist($user_id, $movie_id)) {
            $_SESSION['success'] = "Movie added to wishlist!";
        } else {
            $_SESSION['error'] = "Failed to add to wishlist.";
        }
    } else {
        $_SESSION['info'] = "Movie is already in your wishlist.";
    }
    
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'movies.php'));
    exit();
}
?>