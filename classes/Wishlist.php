<?php
class Wishlist {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function addToWishlist($user_id, $movie_id) {
        $sql = "INSERT INTO wishlist (user_id, movie_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$user_id, $movie_id]);
    }
    
    public function removeFromWishlist($user_id, $movie_id) {
        $sql = "DELETE FROM wishlist WHERE user_id = ? AND movie_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$user_id, $movie_id]);
    }
    
    public function isInWishlist($user_id, $movie_id) {
        $sql = "SELECT id FROM wishlist WHERE user_id = ? AND movie_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id, $movie_id]);
        return $stmt->rowCount() > 0;
    }
    
    public function getUserWishlist($user_id) {
        $sql = "SELECT m.*, w.created_at 
                FROM wishlist w 
                JOIN movies m ON w.movie_id = m.id 
                WHERE w.user_id = ? 
                ORDER BY w.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getWishlistCount($user_id) {
        $sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
?>