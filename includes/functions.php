<?php
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_date($date) {
    return date('M j, Y', strtotime($date));
}

function format_time($time) {
    return date('h:i A', strtotime($time));
}

function get_star_rating($rating) {
    $stars = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    
    for($i = 1; $i <= 5; $i++) {
        if($i <= $full_stars) {
            $stars .= '<i class="fas fa-star text-warning"></i>';
        } elseif($half_star && $i == $full_stars + 1) {
            $stars .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $stars .= '<i class="far fa-star text-warning"></i>';
        }
    }
    
    return $stars;
}

function calculate_average_rating($movie_id, $pdo) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE movie_id = ?");
    $stmt->execute([$movie_id]);
    $result = $stmt->fetch();
    
    return $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
}

function is_featured_movie($movie_id, $pdo) {
    $stmt = $pdo->prepare("SELECT is_featured FROM movies WHERE id = ?");
    $stmt->execute([$movie_id]);
    $movie = $stmt->fetch();
    
    return $movie && $movie['is_featured'];
}
?>