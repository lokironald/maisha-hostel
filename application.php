
<?php
session_start();

include "database.php";

// Protect the application page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$user_id = $_SESSION['user_id'];

// Check whether this resident has already applied
$check = mysqli_prepare(
    $conn,
    "SELECT id FROM applications WHERE user_id = ?"
);

mysqli_stmt_bind_param($check, "i", $user_id);
mysqli_stmt_execute($check);

$existing = mysqli_stmt_get_result($check);

if (mysqli_num_rows($existing) > 0) {
    header("Location: view_application.php");
    exit();
}

mysqli_stmt_close($check);

// Process application form
if (isset($_POST['submit_application'])) {

    $gender = trim($_POST['gender'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year = trim($_POST['year_of_study'] ?? '');
    $room = trim($_POST['room_type'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (
        empty($gender) || empty($phone) ||
        empty($course) || empty($year) ||
        empty($room) || empty($address)
    ) {
        $message = "Please fill in all fields!";
    } else {

        // Check room availability and price
        $roomCheck = mysqli_prepare(
            $conn,
            "SELECT id, total_rooms, occupied_rooms
             FROM rooms
             WHERE room_type = ?"
        );

        mysqli_stmt_bind_param($roomCheck, "s", $room);
        mysqli_stmt_execute($roomCheck);

        $roomResult = mysqli_stmt_get_result($roomCheck);
        $roomData = mysqli_fetch_assoc($roomResult);

        mysqli_stmt_close($roomCheck);

        if (!$roomData) {

            $message = "Invalid room type selected.";

        } elseif ($roomData['occupied_rooms'] >= $roomData['total_rooms']) {

            $message = "Sorry, this room type is currently full. Please select another room.";

        } else {

            // Save application
            $sql = "INSERT INTO applications
            (user_id, gender, phone, course,
            year_of_study, room_type, address)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "issssss",
                $user_id,
                $gender,
                $phone,
                $course,
                $year,
                $room,
                $address
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: view_application.php");
                exit();
            } else {
                $message = "Application failed. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Get available rooms for displaying in the form
$roomsQuery = mysqli_query(
    $conn,
    "SELECT room_type, price, total_rooms, occupied_rooms
     FROM rooms
     ORDER BY id ASC"
);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Maisha Hostel Application</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<h1>Maisha Hostel</h1>

<h2>Resident Room Application Form</h2>

<p>
Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>
</p>

<p><?php echo htmlspecialchars($message); ?></p>

<form id="applicationForm" method="POST">

    <label>Gender:</label><br>
    <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select>
    <br><br>

    <label>Telephone Number:</label><br>
    <input type="tel" name="phone" required>
    <br><br>

    <label>Course/Programme:</label><br>
    <input type="text" name="course" required>
    <br><br>

    <label>Year of Study:</label><br>
    <select name="year_of_study" required>
        <option value="">Select Year</option>
        <option value="Year 1">Year 1</option>
        <option value="Year 2">Year 2</option>
        <option value="Year 3">Year 3</option>
        <option value="Year 4">Year 4</option>
        <option value="Year 5">Year 5</option>
    </select>
    <br><br>

    <label>Preferred Room Type:</label><br>

    <select name="room_type" id="roomType" required>
        <option value="">Select Room Type</option>

        <?php while ($roomRow = mysqli_fetch_assoc($roomsQuery)) { ?>

            <?php
            $available = $roomRow['total_rooms'] - $roomRow['occupied_rooms'];
            $isFull = ($available <= 0);
            ?>

            <option
                value="<?php echo htmlspecialchars($roomRow['room_type']); ?>"
                data-price="<?php echo htmlspecialchars($roomRow['price']); ?>"
                data-available="<?php echo $available; ?>"
                <?php echo $isFull ? 'disabled' : ''; ?>
            >
                <?php echo htmlspecialchars($roomRow['room_type']); ?>
                - UGX <?php echo number_format($roomRow['price']); ?>
                (<?php echo $isFull ? 'Full' : $available . ' available'; ?>)
            </option>

        <?php } ?>

    </select>

    <p id="roomPrice">Select a room to see its price.</p>

    <br><br>

    <label>Home Address:</label><br>
    <textarea name="address" required></textarea>
    <br><br>

    <button type="submit" name="submit_application">
        Submit Application
    </button>

</form>

<br>

<a href="dashboard.php">Back to Dashboard</a>

<script src="script.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const roomType = document.getElementById("roomType");
    const roomPrice = document.getElementById("roomPrice");

    roomType.addEventListener("change", function () {

        const selectedOption = roomType.options[roomType.selectedIndex];

        if (selectedOption.value === "") {
            roomPrice.textContent = "Select a room to see its price.";
            return;
        }

        const price = Number(selectedOption.dataset.price);
        const available = selectedOption.dataset.available;

        roomPrice.textContent =
            "Price: UGX " + price.toLocaleString("en-US") +
            " per semester | Available rooms: " + available;
    });

});
</script>

</body>
</html>
