// Maisha Hostel - JavaScript Validation

document.addEventListener("DOMContentLoaded", function () {

    // 1. REGISTRATION FORM VALIDATION

    const registrationForm = document.getElementById("registrationForm");

    if (registrationForm) {
        registrationForm.addEventListener("submit", function (event) {

            const fullname = registrationForm.querySelector('[name="fullname"]').value.trim();
            const email = registrationForm.querySelector('[name="email"]').value.trim();
            const password = registrationForm.querySelector('[name="password"]').value;

            if (!fullname || !email || !password.trim()) {
                event.preventDefault();
                alert("Please fill in all registration fields.");
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                event.preventDefault();
                alert("Please enter a valid email address.");
                return;
            }

            if (password.length < 8) {
                event.preventDefault();
                alert("Password must be at least 8 characters long.");
                return;
            }

            const confirmPassword = document.getElementById("confirmPassword");

            if (!confirmPassword || password !== confirmPassword.value) {
                event.preventDefault();
                alert("Passwords do not match!");
                return;
            }

        });
    }


    // 2. HOSTEL APPLICATION FORM VALIDATION

    const applicationForm = document.getElementById("applicationForm");

    if (applicationForm) {
        applicationForm.addEventListener("submit", function(event) {

            const phone = applicationForm.querySelector('[name="phone"]');

            if (phone && !/^\+?[0-9]{10,13}$/.test(phone.value.trim())) {
                event.preventDefault();
                alert("Please enter a valid phone number.");
                phone.focus();
                return;
            }

            const inputs = applicationForm.querySelectorAll(
                "input[required], select[required], textarea[required]"
            );

            for (let input of inputs) {
                if (!input.value.trim()) {
                    event.preventDefault();
                    alert("Please fill in all required application fields.");
                    input.focus();
                    return;
                }
            }

            if (!confirm("Are you sure you want to submit your hostel application?")) {
                event.preventDefault();
                return;
            }

            alert("Your application is being submitted!");

            const submitButton = applicationForm.querySelector('[type="submit"]');

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Submitting...";
            }

        });
    }


    // 3. DASHBOARD WELCOME MESSAGE

    const welcomeMessage = document.getElementById("welcomeMessage");

    if (welcomeMessage) {

        const currentHour = new Date().getHours();

        let greeting;

        if (currentHour < 12) {
            greeting = "Good morning!";
        } else if (currentHour < 18) {
            greeting = "Good afternoon!";
        } else {
            greeting = "Good evening!";
        }

        welcomeMessage.textContent = greeting + " Welcome to Maisha Hostel!";
    }


    // 4. APPLICATION STATUS NOTIFICATIONS

    
// 4. Application Status Notifications

const statusElement = document.getElementById("applicationStatus");
const statusMessage = document.getElementById("statusMessage");

if (statusElement && statusMessage) {

    const status = statusElement.dataset.status;

    if (status === "Pending") {

        statusMessage.textContent =
            "Your application is pending. Please wait for administrator approval.";

        statusMessage.classList.add("status-pending");

    } else if (status === "Approved") {

        statusMessage.textContent =
            "Congratulations! Your accommodation application has been approved.";

        statusMessage.classList.add("status-approved");

    } else if (status === "Rejected") {

        statusMessage.textContent =
            "Your accommodation application has been rejected. Please contact the hostel administration.";

        statusMessage.classList.add("status-rejected");

    }

}



    // 5. HOSTEL ROOM PRICE DISPLAY

    const roomType = document.querySelector('[name="room_type"]');
    const roomPrice = document.getElementById("roomPrice");

    if (roomType && roomPrice) {

        function updateRoomPrice() {

            if (roomType.value === "Single Room") {
                roomPrice.textContent = "Price: UGX 500,000 per semester";
            }
            else if (roomType.value === "Shared Room") {
                roomPrice.textContent = "Price: UGX 350,000 per semester";
            }
            else {
                roomPrice.textContent = "Select a room to see its price.";
            }

        }

        roomType.addEventListener("change", updateRoomPrice);

        updateRoomPrice();
    }

});