<?php
class Show {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getShowById($show_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM shows WHERE id = ?");
        $stmt->execute([$show_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getCinemaById($cinema_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM cinemas WHERE id = ?");
        $stmt->execute([$cinema_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAvailableSeats($show_id) {
        $stmt = $this->pdo->prepare("SELECT available_seats FROM shows WHERE id = ?");
        $stmt->execute([$show_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['available_seats'] : 0;
    }
    
    public function getShowsByMovie($movie_id) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, c.name as cinema_name, c.location 
            FROM shows s 
            JOIN cinemas c ON s.cinema_id = c.id 
            WHERE s.movie_id = ? AND s.show_date >= CURDATE() 
            ORDER BY s.show_date, s.show_time
        ");
        $stmt->execute([$movie_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>