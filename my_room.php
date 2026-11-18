
<?php
session_start();
include "database.php";

// Protect resident page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Get resident's room information
$sql = "SELECT
            hr.room_number,
            hr.room_type,
            hr.capacity,
            a.status,
            u.fullname,
            u.email
        FROM applications a
        INNER JOIN users u
            ON a.user_id = u.id
        INNER JOIN room_allocations ra
            ON a.id = ra.application_id
        INNER JOIN hostel_rooms hr
            ON ra.room_id = hr.id
        WHERE a.user_id = ?
        AND a.status = 'Approved'
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$room = mysqli_fetch_assoc($result);

// Get other residents sharing the room
$roommates = [];

if ($room) {

    $room_sql = "SELECT u.fullname
                 FROM room_allocations ra
                 INNER JOIN applications a
                    ON ra.application_id = a.id
                 INNER JOIN users u
                    ON a.user_id = u.id
                 INNER JOIN hostel_rooms hr
                    ON ra.room_id = hr.id
                 WHERE hr.room_number = ?
                 AND a.status = 'Approved'
                 AND u.id != ?";

    $room_stmt = mysqli_prepare($conn, $room_sql);

    mysqli_stmt_bind_param(
        $room_stmt,
        "si",
        $room['room_number'],
        $user_id
    );

    mysqli_stmt_execute($room_stmt);

    $roommates_result = mysqli_stmt_get_result($room_stmt);

    while ($row = mysqli_fetch_assoc($roommates_result)) {
        $roommates[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Accommodation Details - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.room-container {
    max-width: 850px;
    margin: 40px auto;
    padding: 25px;
}

.room-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    margin-top: 20px;
}

.room-card h2 {
    color: #198754;
}

.room-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.detail-box {
    background: #f4f7f5;
    padding: 15px;
    border-radius: 8px;
}

.detail-box strong {
    display: block;
    margin-bottom: 8px;
    color: #198754;
}

.roommate-list {
    margin-top: 20px;
    padding: 15px;
    background: #f4f7f5;
    border-radius: 8px;
}

.room-message {
    padding: 20px;
    background: #fff3cd;
    border-radius: 8px;
    margin-top: 20px;
}

.room-button {
    display: inline-block;
    margin: 20px 10px 0 0;
    padding: 12px 20px;
    background: #198754;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}

.print-button {
    background: #0d6efd;
}

@media print {

    body {
        background: white;
    }

    .room-container {
        margin: 0;
        max-width: 100%;
    }

    .room-button,
    .no-print {
        display: none !important;
    }

    .room-card {
        box-shadow: none;
        border: 1px solid #ccc;
    }

}

</style>

</head>

<body>

<div class="room-container">

    <h1>MAISHA HOSTEL</h1>

    <h2>My Accommodation Details</h2>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Resident'); ?>!
    </p>

    <?php if ($room) { ?>

        <div class="room-card">

            <h2>Accommodation Allocation Confirmation</h2>

            <p>
                This confirms that the following resident has
                been allocated accommodation at Maisha Hostel.
            </p>

            <div class="room-details">

                <div class="detail-box">
                    <strong>Resident Name</strong>
                    <?php echo htmlspecialchars($room['fullname']); ?>
                </div>

                <div class="detail-box">
                    <strong>Email Address</strong>
                    <?php echo htmlspecialchars($room['email']); ?>
                </div>

                <div class="detail-box">
                    <strong>Room Number</strong>
                    <?php echo htmlspecialchars($room['room_number']); ?>
                </div>

                <div class="detail-box">
                    <strong>Room Type</strong>
                    <?php echo htmlspecialchars($room['room_type']); ?>
                </div>

                <div class="detail-box">
                    <strong>Room Capacity</strong>
                    <?php echo (int)$room['capacity']; ?> Students
                </div>

                <div class="detail-box">
                    <strong>Application Status</strong>
                    <?php echo htmlspecialchars($room['status']); ?>
                </div>

            </div>

            <div class="roommate-list">

                <h3>Other Residents Sharing Your Room</h3>

                <?php if (count($roommates) > 0) { ?>

                    <ul>

                        <?php foreach ($roommates as $mate) { ?>

                            <li>
                                <?php echo htmlspecialchars($mate['fullname']); ?>
                            </li>

                        <?php } ?>

                    </ul>

                <?php } else { ?>

                    <p>
                        No other resident is currently allocated
                        to your room.
                    </p>

                <?php } ?>

            </div>

            <p>
                <strong>Date Printed:</strong>
                <?php echo date("d M Y"); ?>
            </p>

            <p>
                Maisha Hostel Administration
            </p>

        </div>

        <button class="room-button print-button"
                onclick="window.print()">
            Print Accommodation Details
        </button>

    <?php } else { ?>

        <div class="room-message">

            <h2>No Room Allocated Yet</h2>

            <p>
                Your application may still be pending approval,
                or your room has not yet been allocated.
            </p>

            <p>Please check again later.</p>

        </div>

    <?php } ?>

    <a href="dashboard.php" class="room-button">
        Back to Dashboard
    </a>

</div>

</body>

</html>
