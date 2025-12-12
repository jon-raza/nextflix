<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../classes/Booking.php';
require_once '../classes/Movie.php';
require_once '../classes/Show.php';

// Manual auth check
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if classes exist before creating objects
try {
    $booking = new Booking($pdo);
    $movie = new Movie($pdo);
    $show = new Show($pdo);
} catch (Error $e) {
    $booking = null;
    $movie = null;
    $show = null;
}

$error = '';
$success = '';

// Get show_id from URL
if (!isset($_GET['show_id']) || empty($_GET['show_id'])) {
    header('Location: movies.php');
    exit();
}

$show_id = $_GET['show_id'];

// Get show details
if ($show && method_exists($show, 'getShowById')) {
    $show_details = $show->getShowById($show_id);
} else {
    // Fallback: Direct database query
    $stmt = $pdo->prepare("SELECT s.*, m.title, m.price, c.name as cinema_name, c.location 
                          FROM shows s 
                          JOIN movies m ON s.movie_id = m.id 
                          JOIN cinemas c ON s.cinema_id = c.id 
                          WHERE s.id = ?");
    $stmt->execute([$show_id]);
    $show_details = $stmt->fetch();
}

if (!$show_details) {
    header('Location: movies.php');
    exit();
}

// Get movie details
if ($movie && method_exists($movie, 'getMovieById')) {
    $movie_details = $movie->getMovieById($show_details['movie_id']);
} else {
    // Fallback: Direct database query
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$show_details['movie_id']]);
    $movie_details = $stmt->fetch();
}

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seats_booked = $_POST['seats_booked'];
    $selected_seats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : '';
    
    // Calculate total amount
    $total_amount = $seats_booked * $movie_details['price'];
    
    try {
        // Start transaction for data consistency
        $pdo->beginTransaction();
        
        // Create booking using the CORRECT table structure for revenue tracking
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, show_id, seats_booked, selected_seats, total_amount, status, booking_date) 
                              VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())");
        $stmt->execute([
            $user_id, 
            $show_id,
            $seats_booked,
            $selected_seats,
            $total_amount
        ]);
        $booking_id = $pdo->lastInsertId();
        
        if ($booking_id) {
            // Update available seats
            $update_stmt = $pdo->prepare("UPDATE shows SET available_seats = available_seats - ? WHERE id = ?");
            $update_stmt->execute([$seats_booked, $show_id]);
            
            // ============ REVENUE UPDATE FIX ============
            // IMMEDIATE REVENUE UPDATE - DIRECT METHOD
            $total_rev = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as rev FROM bookings WHERE status = 'confirmed'")->fetch()['rev'];
            $daily_rev = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as rev FROM bookings WHERE status = 'confirmed' AND DATE(booking_date) = CURDATE()")->fetch()['rev'];
            $weekly_rev = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as rev FROM bookings WHERE status = 'confirmed' AND YEARWEEK(booking_date) = YEARWEEK(CURDATE())")->fetch()['rev'];
            $monthly_rev = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as rev FROM bookings WHERE status = 'confirmed' AND YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())")->fetch()['rev'];
            
            $update_revenue = $pdo->prepare("
                UPDATE revenue_stats 
                SET total_revenue = ?, 
                    daily_revenue = ?,
                    weekly_revenue = ?,
                    monthly_revenue = ?,
                    last_updated = NOW()
                WHERE id = 1
            ");
            $update_revenue->execute([$total_rev, $daily_rev, $weekly_rev, $monthly_rev]);
            // ============ REVENUE UPDATE FIX END ============
            
            // Commit transaction
            $pdo->commit();
            
            $success = "Booking confirmed successfully! Booking ID: #" . $booking_id;
        } else {
            $pdo->rollBack();
            $error = "Failed to create booking. Please try again.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Booking failed: " . $e->getMessage();
    }
}

// Get available seats for this show
$stmt = $pdo->prepare("SELECT available_seats FROM shows WHERE id = ?");
$stmt->execute([$show_id]);
$result = $stmt->fetch();
$available_seats = $result ? $result['available_seats'] : 0;
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Book Your Tickets</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <div class="mt-2">
                                <a href="my-bookings.php" class="btn btn-info btn-sm">View My Bookings</a>
                                <a href="movies.php" class="btn btn-secondary btn-sm">Book More Movies</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Only show form if no success message -->
                    <?php if (!$success): ?>
                    <!-- Movie Details -->
                    <div class="movie-details mb-4 p-3 border rounded">
                        <h5><?php echo htmlspecialchars($movie_details['title']); ?></h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Cinema:</strong> <?php echo htmlspecialchars($show_details['cinema_name']); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($show_details['location']); ?></p>
                                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($show_details['show_date'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($show_details['show_time'])); ?></p>
                                <p><strong>Price per ticket:</strong> $<?php echo $movie_details['price']; ?></p>
                                <p><strong>Available Seats:</strong> <span class="badge bg-<?php echo $available_seats > 0 ? 'success' : 'danger'; ?>"><?php echo $available_seats; ?></span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Form -->
                    <form method="POST" action="">
                        <div class="form-group mb-3">
                            <label for="seats_booked"><strong>Number of Seats</strong></label>
                            <select class="form-control" id="seats_booked" name="seats_booked" required>
                                <option value="">Select number of seats</option>
                                <?php 
                                $max_seats = min(10, $available_seats);
                                for ($i = 1; $i <= $max_seats; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> Seat<?php echo $i > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                            <?php if ($available_seats == 0): ?>
                                <small class="text-danger">No seats available for this show</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="selected_seats"><strong>Preferred Seats (Optional)</strong></label>
                            <input type="text" class="form-control" id="selected_seats" name="selected_seats" 
                                   placeholder="e.g., A1, A2, A3">
                            <small class="form-text text-muted">Enter seat numbers separated by commas</small>
                        </div>

                        <div class="price-summary mb-4 p-3 bg-light rounded">
                            <h6>Price Summary</h6>
                            <div id="priceDetails">
                                <p>Seats: <span id="seatCount">0</span> x $<?php echo $movie_details['price']; ?> = $<span id="totalAmount">0.00</span></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php if ($available_seats > 0): ?>
                                <button type="submit" class="btn btn-success btn-lg btn-block">Confirm Booking</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-lg btn-block" disabled>No Seats Available</button>
                            <?php endif; ?>
                            <a href="movie-details.php?id=<?php echo $movie_details['id']; ?>" class="btn btn-outline-secondary btn-block mt-2">Back to Movie Details</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time price calculation
document.getElementById('seats_booked').addEventListener('change', function() {
    const seatCount = parseInt(this.value);
    const pricePerSeat = <?php echo $movie_details['price']; ?>;
    const totalAmount = seatCount * pricePerSeat;
    
    document.getElementById('seatCount').textContent = seatCount;
    document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
});
</script>

<?php include '../includes/footer.php'; ?>