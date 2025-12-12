<?php
// Session start at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if (isset($_GET['movie_id'])) {
    $movie_id = $_GET['movie_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if movie exists
        $movie_stmt = $pdo->prepare("SELECT id FROM movies WHERE id = ?");
        $movie_stmt->execute([$movie_id]);
        
        if ($movie_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'error' => 'Movie not found']);
            exit();
        }
        
        // Check if already in wishlist
        $check_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND movie_id = ?");
        $check_stmt->execute([$user_id, $movie_id]);
        
        if ($check_stmt->rowCount() > 0) {
            // Remove from wishlist
            $remove_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND movie_id = ?");
            if ($remove_stmt->execute([$user_id, $movie_id])) {
                echo json_encode(['success' => true, 'action' => 'removed']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to remove from wishlist']);
            }
        } else {
            // Add to wishlist
            $add_stmt = $pdo->prepare("INSERT INTO wishlist (user_id, movie_id) VALUES (?, ?)");
            if ($add_stmt->execute([$user_id, $movie_id])) {
                echo json_encode(['success' => true, 'action' => 'added']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add to wishlist']);
            }
        }
    } catch (PDOException $e) {
        error_log("Wishlist error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No movie ID provided']);
}
?>