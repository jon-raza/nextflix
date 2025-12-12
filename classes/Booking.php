<?php
class Booking {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createBooking($data) {
        try {
            $this->pdo->beginTransaction();
            
            // Check if enough seats available
            $show_stmt = $this->pdo->prepare("SELECT available_seats FROM shows WHERE id = ?");
            $show_stmt->execute([$data['show_id']]);
            $show = $show_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$show || $show['available_seats'] < $data['seats_booked']) {
                throw new Exception("Not enough seats available.");
            }
            
            // Insert booking
            $stmt = $this->pdo->prepare("
                INSERT INTO bookings (user_id, show_id, seats_booked, selected_seats, total_amount, status) 
                VALUES (?, ?, ?, ?, ?, 'confirmed')
            ");
            
            $stmt->execute([
                $data['user_id'],
                $data['show_id'],
                $data['seats_booked'],
                $data['selected_seats'],
                $data['total_amount']
            ]);
            
            $booking_id = $this->pdo->lastInsertId();
            
            // Update available seats
            $update_stmt = $this->pdo->prepare("
                UPDATE shows SET available_seats = available_seats - ? WHERE id = ?
            ");
            $update_stmt->execute([$data['seats_booked'], $data['show_id']]);
            
            $this->pdo->commit();
            return $booking_id;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function getUserBookings($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, m.title, m.thumbnail_url, c.name as cinema_name, 
                   s.show_date, s.show_time
            FROM bookings b
            JOIN shows s ON b.show_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN cinemas c ON s.cinema_id = c.id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getBookingById($booking_id) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, m.title, m.thumbnail_url, c.name as cinema_name, c.location,
                   s.show_date, s.show_time, u.full_name, u.email
            FROM bookings b
            JOIN shows s ON b.show_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN cinemas c ON s.cinema_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>