    <?php
    require_once '../config/database.php';
    require_once '../includes/functions.php';
    require_once '../includes/auth-check.php';

    $page_title = "Select Seats";

    // Check if we have show_id or movie_id
    if(isset($_GET['show_id'])) {
        $show_id = $_GET['show_id'];
    } elseif(isset($_GET['movie_id'])) {
        // If movie_id is provided, get the first available show for that movie
        $movie_id = $_GET['movie_id'];
        $first_show_stmt = $pdo->prepare("SELECT s.id 
                                        FROM shows s 
                                        WHERE s.movie_id = ? AND s.available_seats > 0 AND s.show_date >= CURDATE() 
                                        ORDER BY s.show_date 
                                        LIMIT 1");
        $first_show_stmt->execute([$movie_id]);
        $first_show = $first_show_stmt->fetch();
        
        if($first_show) {
            $show_id = $first_show['id'];
        } else {
            // If no shows available, redirect to movie details
            header("Location: movie-details.php?id=" . $movie_id);
            exit();
        }
    } else {
        header("Location: movies.php");
        exit();
    }

    // Get show details
    $stmt = $pdo->prepare("SELECT s.*, m.title, m.price, c.name as cinema_name, c.location 
                        FROM shows s 
                        JOIN movies m ON s.movie_id = m.id 
                        JOIN cinemas c ON s.cinema_id = c.id 
                        WHERE s.id = ?");
    $stmt->execute([$show_id]);
    $show = $stmt->fetch();

    if(!$show) {
        header("Location: movies.php");
        exit();
    }

    // Get available seats for this show
    $seats_stmt = $pdo->prepare("SELECT * FROM show_seats WHERE show_id = ? AND is_available = TRUE ORDER BY seat_number");
    $seats_stmt->execute([$show_id]);
    $available_seats = $seats_stmt->fetchAll();

    // Handle seat selection
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_seats'])) {
        $selected_seats = $_POST['selected_seats'];
        $seat_count = count($selected_seats);
        $total_amount = $seat_count * $show['price'];
        
        if($seat_count == 0) {
            $error = "Please select at least one seat!";
        } else {
            // Create booking
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, show_id, seats_booked, total_amount, selected_seats) VALUES (?, ?, ?, ?, ?)");
            $selected_seats_str = implode(', ', $selected_seats);
            
            if($stmt->execute([$_SESSION['user_id'], $show_id, $seat_count, $total_amount, $selected_seats_str])) {
                $booking_id = $pdo->lastInsertId();
                
                // Update seat availability
                $update_stmt = $pdo->prepare("UPDATE show_seats SET is_available = FALSE WHERE show_id = ? AND seat_number = ?");
                foreach($selected_seats as $seat) {
                    $update_stmt->execute([$show_id, $seat]);
                }
                
                // Update available seats count in shows table
                $update_show_stmt = $pdo->prepare("UPDATE shows SET available_seats = available_seats - ? WHERE id = ?");
                $update_show_stmt->execute([$seat_count, $show_id]);
                
                $success = "Booking confirmed successfully!";
                header("refresh:2;url=bookings.php");
            } else {
                $error = "Booking failed! Please try again.";
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title; ?> - Nextflix</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                <link rel="icon" type="image/x-icon" href="../assets/images/Nextflixfavicon.png">

        <style>
            :root {
                --netflix-red: #e50914;
                --netflix-dark: #141414;
                --netflix-gray: #2f2f2f;
                --netflix-light: #f5f5f1;
            }
            
            body {
                background: var(--netflix-dark);
                color: white;
                font-family: 'Helvetica Neue', Arial, sans-serif;
                padding-top: 70px;
            }
            
            .netflix-navbar {
                background: var(--netflix-dark) !important;
                padding: 0.8rem 0;
                border-bottom: 1px solid #333;
            }
            
            .netflix-brand {
                color: var(--netflix-red) !important;
                font-weight: bold;
                font-size: 1.8rem;
            }
            
            .nav-link {
                color: white !important;
                font-weight: 500;
                transition: color 0.3s;
                padding: 0.5rem 1rem !important;
            }
            
            .nav-link:hover {
                color: var(--netflix-red) !important;
            }
            
            .netflix-card {
                background: var(--netflix-gray);
                border: none;
                border-radius: 15px;
                color: white;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
            
            .netflix-card-header {
                background: linear-gradient(45deg, var(--netflix-red), #b81d24);
                border: none;
                border-radius: 15px 15px 0 0 !important;
                color: white;
                font-weight: bold;
                padding: 1.5rem !important;
            }
            
            .btn-netflix {
                background: var(--netflix-red);
                border: none;
                color: white;
                font-weight: bold;
                padding: 12px 30px;
                border-radius: 8px;
                transition: all 0.3s;
            }
            
            .btn-netflix:hover {
                background: #b81d24;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
                color: white;
            }
            
            /* Seat Selection Styles */
            .screen {
                background: linear-gradient(45deg, #333, #555);
                color: white;
                padding: 20px;
                text-align: center;
                margin: 2rem 0;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }
            
            .seats-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                margin: 2rem 0;
            }
            
            .seat-row {
                display: flex;
                gap: 8px;
                align-items: center;
            }
            
            .row-label {
                color: var(--netflix-light);
                font-weight: bold;
                width: 30px;
                text-align: center;
            }
            
            .seat {
                width: 35px;
                height: 35px;
                background: #28a745;
                border: none;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                font-size: 0.8rem;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .seat:hover {
                transform: scale(1.1);
                background: #20c997;
            }
            
            .seat.selected {
                background: var(--netflix-red);
                transform: scale(1.1);
            }
            
            .seat.occupied {
                background: #6c757d;
                cursor: not-allowed;
            }
            
            .seat.occupied:hover {
                transform: none;
                background: #6c757d;
            }
            
            .seat-info {
                display: flex;
                justify-content: center;
                gap: 2rem;
                margin: 1rem 0;
            }
            
            .seat-info-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .seat-sample {
                width: 20px;
                height: 20px;
                border-radius: 3px;
            }
            
            .selected-seats {
                background: var(--netflix-gray);
                border-radius: 10px;
                padding: 1rem;
                margin: 1rem 0;
            }
            
            .selected-seats-list {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-top: 0.5rem;
            }
            
            .seat-badge {
                background: var(--netflix-red);
                color: white;
                padding: 5px 10px;
                border-radius: 15px;
                font-size: 0.8rem;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <!-- Netflix Style Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark netflix-navbar fixed-top">
            <div class="container">
                <a class="navbar-brand netflix-brand" href="dashboard.php">
                    <i class="fas fa-film me-2"></i>NEXTFLIX
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-1"></i>Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="movies.php">
                                <i class="fas fa-film me-1"></i>Movies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-ticket-alt me-1"></i>My Bookings
                            </a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i><?php echo $_SESSION['username']; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="bookings.php"><i class="fas fa-ticket-alt me-2"></i>My Bookings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Login</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="netflix-card">
                        <div class="netflix-card-header text-center">
                            <h3 class="mb-0"><i class="fas fa-chair me-2"></i>Select Your Seats</h3>
                        </div>
                        <div class="card-body p-4">
                            <!-- Show Details -->
                            <div class="netflix-card p-4 mb-4">
                                <h4 class="text-warning mb-3"><?php echo $show['title']; ?></h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Cinema:</strong> <?php echo $show['cinema_name']; ?></p>
                                        <p class="mb-0"><strong>Location:</strong> <?php echo $show['location']; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Date:</strong> <?php echo format_date($show['show_date']); ?></p>
                                        <p class="mb-0"><strong>Time:</strong> <?php echo format_time($show['show_time']); ?></p>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <span class="badge bg-success fs-6">
                                            <i class="fas fa-tag me-1"></i>Price: $<?php echo $show['price']; ?> per seat
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <?php if(isset($success)): ?>
                                <div class="alert alert-success">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle fa-2x me-3"></i>
                                        <div>
                                            <h5 class="mb-1">Booking Confirmed!</h5>
                                            <p class="mb-1"><?php echo $success; ?></p>
                                            <?php if(isset($booking_id)): ?>
                                                <p class="mb-0"><strong>Booking ID:</strong> #<?php echo $booking_id; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if(isset($error)): ?>
                                <div class="alert alert-danger">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                        <div>
                                            <h5 class="mb-0"><?php echo $error; ?></h5>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if(count($available_seats) > 0 && !isset($success)): ?>
                            <!-- Screen -->
                            <div class="screen">
                                <h4 class="mb-0"><i class="fas fa-film me-2"></i>SCREEN</h4>
                            </div>

                            <!-- Seat Legend -->
                            <div class="seat-info">
                                <div class="seat-info-item">
                                    <div class="seat-sample" style="background: #28a745;"></div>
                                    <span>Available</span>
                                </div>
                                <div class="seat-info-item">
                                    <div class="seat-sample" style="background: var(--netflix-red);"></div>
                                    <span>Selected</span>
                                </div>
                                <div class="seat-info-item">
                                    <div class="seat-sample" style="background: #6c757d;"></div>
                                    <span>Occupied</span>
                                </div>
                            </div>

                            <!-- Seat Selection Form -->
                            <form method="POST" action="" id="seatForm">
                                <!-- Seats will be dynamically generated here -->
                                <div class="seats-container" id="seatsContainer">
                                    <?php
                                    // Group seats by row
                                    $seats_by_row = [];
                                    foreach($available_seats as $seat) {
                                        $row = $seat['seat_number'][0]; // Get first character (A, B, etc.)
                                        $seats_by_row[$row][] = $seat;
                                    }
                                    
                                    // Get all seats (including occupied) for layout
                                    $all_seats_stmt = $pdo->prepare("SELECT * FROM show_seats WHERE show_id = ? ORDER BY seat_number");
                                    $all_seats_stmt->execute([$show_id]);
                                    $all_seats = $all_seats_stmt->fetchAll();
                                    
                                    $all_seats_by_row = [];
                                    foreach($all_seats as $seat) {
                                        $row = $seat['seat_number'][0];
                                        $all_seats_by_row[$row][] = $seat;
                                    }
                                    
                                    ksort($all_seats_by_row);
                                    
                                    foreach($all_seats_by_row as $row => $seats): 
                                    ?>
                                    <div class="seat-row">
                                        <div class="row-label"><?php echo $row; ?></div>
                                        <?php foreach($seats as $seat): 
                                            $is_available = $seat['is_available'];
                                        ?>
                                        <button type="button" 
                                                class="seat <?php echo $is_available ? 'available' : 'occupied'; ?>" 
                                                data-seat="<?php echo $seat['seat_number']; ?>"
                                                <?php echo $is_available ? '' : 'disabled'; ?>>
                                            <?php echo substr($seat['seat_number'], 1); ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Selected Seats Display -->
                                <div class="selected-seats">
                                    <h6 class="mb-2">Selected Seats:</h6>
                                    <div class="selected-seats-list" id="selectedSeatsList">
                                        <span class="text-muted">No seats selected</span>
                                    </div>
                                    <input type="hidden" name="selected_seats[]" id="selectedSeatsInput">
                                </div>

                                <!-- Price Calculation -->
                                <div class="netflix-card p-4 mt-4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Price Calculation:</h6>
                                            <p class="mb-1">Seats Selected: <span id="seatCount">0</span></p>
                                            <p class="mb-1">Price per seat: $<?php echo $show['price']; ?></p>
                                            <h5 class="text-success mt-2">Total: $<span id="totalAmount">0.00</span></h5>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-center justify-content-end">
                                            <button type="submit" class="btn-netflix btn-lg">
                                                <i class="fas fa-credit-card me-2"></i>Confirm Booking
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-warning text-center py-5">
                                    <i class="fas fa-exclamation-triangle fa-4x mb-3"></i>
                                    <h3>No Seats Available!</h3>
                                    <p class="mb-4">Sorry, all seats for this show have been booked.</p>
                                    <a href="movies.php" class="btn-netflix">
                                        <i class="fas fa-film me-2"></i>Browse Other Movies
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectedSeats = new Set();
            const pricePerSeat = <?php echo $show['price']; ?>;
            
            // Seat selection handling
            document.querySelectorAll('.seat.available').forEach(seat => {
                seat.addEventListener('click', function() {
                    const seatNumber = this.getAttribute('data-seat');
                    
                    if (selectedSeats.has(seatNumber)) {
                        // Deselect seat
                        selectedSeats.delete(seatNumber);
                        this.classList.remove('selected');
                    } else {
                        // Select seat
                        selectedSeats.add(seatNumber);
                        this.classList.add('selected');
                    }
                    
                    updateSelectionDisplay();
                });
            });
            
            function updateSelectionDisplay() {
                const selectedSeatsList = document.getElementById('selectedSeatsList');
                const selectedSeatsInput = document.getElementById('selectedSeatsInput');
                const seatCount = document.getElementById('seatCount');
                const totalAmount = document.getElementById('totalAmount');
                
                // Update selected seats display
                if (selectedSeats.size > 0) {
                    selectedSeatsList.innerHTML = '';
                    selectedSeats.forEach(seat => {
                        const badge = document.createElement('span');
                        badge.className = 'seat-badge';
                        badge.textContent = seat;
                        selectedSeatsList.appendChild(badge);
                    });
                    
                    // Update hidden input
                    selectedSeatsInput.value = Array.from(selectedSeats).join(',');
                } else {
                    selectedSeatsList.innerHTML = '<span class="text-muted">No seats selected</span>';
                    selectedSeatsInput.value = '';
                }
                
                // Update price calculation
                seatCount.textContent = selectedSeats.size;
                totalAmount.textContent = (selectedSeats.size * pricePerSeat).toFixed(2);
            }
            
            // Form submission validation
            document.getElementById('seatForm').addEventListener('submit', function(e) {
                if (selectedSeats.size === 0) {
                    e.preventDefault();
                    alert('Please select at least one seat!');
                }
            });
        });
        </script>
    </body>
    </html>