// Nextflix - Booking System JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initBookingSystem();
    initSeatSelection();
    initPaymentCalculator();
});

// Booking System Initialization
function initBookingSystem() {
    console.log('Nextflix Booking System Initialized');
    
    // Initialize date pickers
    initDatePickers();
    
    // Initialize time selection
    initTimeSelection();
    
    // Initialize booking form validation
    initBookingFormValidation();
}

// Seat Selection System
function initSeatSelection() {
    const seatCheckboxes = document.querySelectorAll('.seat-checkbox');
    const selectedSeatsDisplay = document.getElementById('selectedSeats');
    const totalPriceDisplay = document.getElementById('totalPrice');
    const seatCountDisplay = document.getElementById('seatCount');
    
    let selectedSeats = [];
    const seatPrice = parseFloat(document.getElementById('seatPrice').value) || 10.00;
    
    seatCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const seatId = this.value;
            const seatElement = this.parentElement;
            
            if (this.checked) {
                if (selectedSeats.length >= 10) { // Max 10 seats per booking
                    this.checked = false;
                    showToast('Maximum 10 seats can be selected per booking', 'warning');
                    return;
                }
                
                selectedSeats.push(seatId);
                seatElement.classList.add('selected');
            } else {
                selectedSeats = selectedSeats.filter(seat => seat !== seatId);
                seatElement.classList.remove('selected');
            }
            
            updateSeatSelectionDisplay();
        });
    });
    
    function updateSeatSelectionDisplay() {
        const totalPrice = selectedSeats.length * seatPrice;
        
        // Update displays
        if (selectedSeatsDisplay) {
            selectedSeatsDisplay.textContent = selectedSeats.join(', ') || 'No seats selected';
        }
        
        if (totalPriceDisplay) {
            totalPriceDisplay.textContent = totalPrice.toFixed(2);
        }
        
        if (seatCountDisplay) {
            seatCountDisplay.textContent = selectedSeats.length;
        }
        
        // Update hidden input for form submission
        const selectedSeatsInput = document.getElementById('selectedSeatsInput');
        if (selectedSeatsInput) {
            selectedSeatsInput.value = selectedSeats.join(',');
        }
        
        // Enable/disable booking button
        const bookButton = document.getElementById('bookButton');
        if (bookButton) {
            bookButton.disabled = selectedSeats.length === 0;
        }
    }
    
    // Initialize seat layout
    generateSeatLayout();
}

// Generate Dynamic Seat Layout
function generateSeatLayout() {
    const seatLayout = document.getElementById('seatLayout');
    if (!seatLayout) return;
    
    const totalSeats = 80; // Example: 80 seats total
    const seatsPerRow = 10;
    const totalRows = Math.ceil(totalSeats / seatsPerRow);
    
    let html = '';
    
    for (let row = 1; row <= totalRows; row++) {
        html += `<div class="row mb-2 justify-content-center">`;
        html += `<div class="col-auto"><span class="badge bg-dark me-2">Row ${String.fromCharCode(64 + row)}</span></div>`;
        
        for (let seat = 1; seat <= seatsPerRow; seat++) {
            const seatNumber = (row - 1) * seatsPerRow + seat;
            if (seatNumber > totalSeats) break;
            
            const seatId = `R${row}S${seat}`;
            const isAvailable = Math.random() > 0.3; // 70% available for demo
            
            html += `
                <div class="col-auto">
                    <input type="checkbox" 
                           class="seat-checkbox d-none" 
                           id="seat-${seatId}"
                           value="${seatId}"
                           ${!isAvailable ? 'disabled' : ''}>
                    <label for="seat-${seatId}" 
                           class="seat-label ${isAvailable ? 'available' : 'booked'}">
                        ${seatNumber}
                    </label>
                </div>
            `;
        }
        
        html += `</div>`;
    }
    
    seatLayout.innerHTML = html;
    
    // Re-initialize seat selection for dynamically generated seats
    setTimeout(() => {
        initSeatSelection();
    }, 100);
}

// Payment Calculator
function initPaymentCalculator() {
    const seatCountInput = document.getElementById('seatCountInput');
    const pricePerSeatInput = document.getElementById('pricePerSeat');
    const totalAmountDisplay = document.getElementById('totalAmount');
    const confirmAmountDisplay = document.getElementById('confirmAmount');
    
    if (seatCountInput && pricePerSeatInput) {
        const pricePerSeat = parseFloat(pricePerSeatInput.value) || 0;
        
        function calculateTotal() {
            const seatCount = parseInt(seatCountInput.value) || 0;
            const totalAmount = seatCount * pricePerSeat;
            
            if (totalAmountDisplay) {
                totalAmountDisplay.textContent = totalAmount.toFixed(2);
            }
            
            if (confirmAmountDisplay) {
                confirmAmountDisplay.textContent = totalAmount.toFixed(2);
            }
            
            // Update hidden total input
            const totalAmountInput = document.getElementById('totalAmountInput');
            if (totalAmountInput) {
                totalAmountInput.value = totalAmount.toFixed(2);
            }
        }
        
        seatCountInput.addEventListener('input', calculateTotal);
        seatCountInput.addEventListener('change', calculateTotal);
        
        // Initial calculation
        calculateTotal();
    }
}

// Date Picker Initialization
function initDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        input.setAttribute('min', today);
        
        // Set max date to 1 year from today
        const nextYear = new Date();
        nextYear.setFullYear(nextYear.getFullYear() + 1);
        const maxDate = nextYear.toISOString().split('T')[0];
        input.setAttribute('max', maxDate);
        
        // Add custom styling
        input.classList.add('custom-date-input');
    });
}

// Time Selection
function initTimeSelection() {
    const timeSelects = document.querySelectorAll('.time-select');
    
    timeSelects.forEach(select => {
        // Generate time slots
        const timeSlots = generateTimeSlots();
        
        timeSlots.forEach(slot => {
            const option = document.createElement('option');
            option.value = slot.value;
            option.textContent = slot.label;
            select.appendChild(option);
        });
    });
}

function generateTimeSlots() {
    const slots = [];
    const startHour = 10; // 10 AM
    const endHour = 22;   // 10 PM
    
    for (let hour = startHour; hour <= endHour; hour++) {
        for (let minute = 0; minute < 60; minute += 30) { // Every 30 minutes
            const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            const displayTime = formatTimeForDisplay(timeString);
            
            slots.push({
                value: timeString,
                label: displayTime
            });
        }
    }
    
    return slots;
}

function formatTimeForDisplay(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    
    return `${displayHour}:${minutes} ${ampm}`;
}

// Booking Form Validation
function initBookingFormValidation() {
    const bookingForms = document.querySelectorAll('.booking-form');
    
    bookingForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateBookingForm(this)) {
                e.preventDefault();
                showToast('Please fill in all required fields correctly', 'warning');
            }
        });
    });
}

function validateBookingForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            markFieldInvalid(field);
            isValid = false;
        } else {
            markFieldValid(field);
        }
    });
    
    // Validate seat selection
    const selectedSeatsInput = document.getElementById('selectedSeatsInput');
    if (selectedSeatsInput && !selectedSeatsInput.value) {
        showToast('Please select at least one seat', 'warning');
        isValid = false;
    }
    
    return isValid;
}

function markFieldInvalid(field) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
}

function markFieldValid(field) {
    field.classList.add('is-valid');
    field.classList.remove('is-invalid');
}

// Booking Confirmation
function confirmBooking(bookingData) {
    return new Promise((resolve, reject) => {
        // Simulate API call
        setTimeout(() => {
            if (Math.random() > 0.1) { // 90% success rate for demo
                resolve({
                    success: true,
                    bookingId: 'BK' + Date.now(),
                    message: 'Booking confirmed successfully!'
                });
            } else {
                reject(new Error('Booking failed. Please try again.'));
            }
        }, 2000);
    });
}

// Export functions for global access
window.BookingSystem = {
    confirmBooking,
    generateSeatLayout,
    validateBookingForm
};

// CSS for booking components
const bookingStyles = `
<style>
.seat-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #28a745;
    color: white;
    border-radius: 8px;
    margin: 2px;
    cursor: pointer;
    font-weight: bold;
    font-size: 12px;
    transition: all 0.3s ease;
    user-select: none;
}

.seat-label.available:hover {
    background: #218838;
    transform: scale(1.1);
}

.seat-label.selected {
    background: #007bff;
    transform: scale(1.1);
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
}

.seat-label.booked {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.seat-label.occupied {
    background: #dc3545;
    cursor: not-allowed;
}

.screen-display {
    background: linear-gradient(45deg, #495057, #6c757d);
    color: white;
    text-align: center;
    padding: 15px;
    margin: 20px 0;
    border-radius: 10px;
    font-weight: bold;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.booking-summary {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
}

.booking-timeline {
    display: flex;
    justify-content: space-between;
    margin: 30px 0;
    position: relative;
}

.booking-step {
    text-align: center;
    flex: 1;
    position: relative;
    z-index: 2;
}

.booking-step::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    right: -50%;
    height: 2px;
    background: #dee2e6;
    z-index: 1;
}

.booking-step:last-child::before {
    display: none;
}

.booking-step.active::before {
    background: #007bff;
}

.step-number {
    width: 40px;
    height: 40px;
    background: #dee2e6;
    color: #6c757d;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: bold;
    position: relative;
    z-index: 2;
}

.booking-step.active .step-number {
    background: #007bff;
    color: white;
}

.step-label {
    font-size: 14px;
    color: #6c757d;
}

.booking-step.active .step-label {
    color: #007bff;
    font-weight: bold;
}

.custom-date-input {
    background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23007bff' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/%3E%3C/svg%3E") no-repeat right 10px center;
    background-size: 16px;
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin: 20px 0;
}

.payment-method {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method:hover {
    border-color: #007bff;
}

.payment-method.selected {
    border-color: #007bff;
    background: #f8f9ff;
}

.payment-method i {
    font-size: 24px;
    margin-bottom: 8px;
    display: block;
}

@media (max-width: 768px) {
    .seat-label {
        width: 35px;
        height: 35px;
        font-size: 10px;
    }
    
    .booking-timeline {
        flex-direction: column;
        gap: 20px;
    }
    
    .booking-step::before {
        display: none;
    }
}
</style>
`;

// Inject styles into document
document.head.insertAdjacentHTML('beforeend', bookingStyles);