<?php
// clear_booking_success.php
session_start();
if(isset($_SESSION['booking_success'])) {
    unset($_SESSION['booking_success']);
}
echo "OK";
?>