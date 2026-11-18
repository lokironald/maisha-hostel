
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get resident ID
$resident_id = (int)($_GET['id'] ?? 0);

if ($resident_id <= 0) {
    die("Invalid resident ID.");
}

// Retrieve resident and application information
$sql = "SELECT
            u.id,
            u.fullname,
            u.email,
            a.id AS application_id,
            a.gender,
            a.phone,
            a.course,
            a.year_of_study,
            a.room_type,
            a.address,
            a.status,
            hr.room_number,
            hr.capacity,
            hr.room_type AS allocated_room_type

        FROM users u

        LEFT JOIN applications a
            ON u.id = a.user_id

        LEFT JOIN room_allocations ra
            ON a.id = ra.application_id

        LEFT JOIN hostel_rooms hr
            ON ra.room_id = hr.id

        WHERE u.id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, "i", $resident_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$resident = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Check whether resident exists
if (!$resident) {
    die("Resident not found.");
}

// Retrieve roommates if a room has been allocated
$roommates = [];

if (!empty($resident['application_id']) &&
    !empty($resident['room_number'])) {

    $room_id_query = mysqli_prepare(
        $conn,
        "SELECT room_id
         FROM room_allocations
         WHERE application_id = ?"
    );

    mysqli_stmt_bind_param(
        $room_id_query,
        "i",
        $resident['application_id']
    );

    mysqli_stmt_execute($room_id_query);

    $room_id_result = mysqli_stmt_get_result($room_id_query);
    $allocation = mysqli_fetch_assoc($room_id_result);

    mysqli_stmt_close($room_id_query);

    if ($allocation) {

        $roommates_query = mysqli_prepare(
            $conn,
            "SELECT u.fullname, u.email
             FROM room_allocations ra
             INNER JOIN applications a
                ON ra.application_id = a.id
             INNER JOIN users u
                ON a.user_id = u.id
             WHERE ra.room_id = ?
             AND u.id != ?"
        );

        mysqli_stmt_bind_param(
            $roommates_query,
            "ii",
            $allocation['room_id'],
            $resident_id
        );

        mysqli_stmt_execute($roommates_query);

        $roommates_result = mysqli_stmt_get_result($roommates_query);

        while ($roommate = mysqli_fetch_assoc($roommates_result)) {
            $roommates[] = $roommate;
        }

        mysqli_stmt_close($roommates_query);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Resident Details - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .details-container {
            max-width: 950px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #198754;
            text-align: center;
        }

        .details-card {
            padding: 20px;
            margin: 20px 0;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .details-card h2 {
            color: #198754;
            border-bottom: 2px solid #198754;
            padding-bottom: 10px;
        }

        .detail-row {
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .detail-row strong {
            display: inline-block;
            width: 200px;
        }

        .approved {
            color: green;
            font-weight: bold;
        }

        .pending {
            color: #b8860b;
            font-weight: bold;
        }

        .rejected {
            color: red;
            font-weight: bold;
        }

        .not-allocated {
            color: #b8860b;
            font-weight: bold;
        }

        .room-allocated {
            color: green;
            font-weight: bold;
        }

        .action-buttons {
            margin-top: 25px;
        }

        .action-buttons a,
        .action-buttons button {
            display: inline-block;
            padding: 11px 18px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            background: #198754;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .action-buttons a:hover,
        .action-buttons button:hover {
            background: #146c43;
        }

        @media print {
            .action-buttons {
                display: none;
            }

            .details-container {
                box-shadow: none;
            }
        }

    </style>

</head>

<body>

<div class="details-container">

    <h1>Maisha Hostel</h1>

    <h2 style="text-align:center;">
        Resident Details
    </h2>

    <!-- Personal Information -->

    <div class="details-card">

        <h2>Personal Information</h2>

        <div class="detail-row">
            <strong>Resident ID:</strong>
            <?php echo (int)$resident['id']; ?>
        </div>

        <div class="detail-row">
            <strong>Full Name:</strong>
            <?php echo htmlspecialchars($resident['fullname']); ?>
        </div>

        <div class="detail-row">
            <strong>Email Address:</strong>
            <?php echo htmlspecialchars($resident['email']); ?>
        </div>

        <div class="detail-row">
            <strong>Telephone:</strong>
            <?php echo htmlspecialchars($resident['phone'] ?? 'Not provided'); ?>
        </div>

        <div class="detail-row">
            <strong>Gender:</strong>
            <?php echo htmlspecialchars($resident['gender'] ?? 'Not provided'); ?>
        </div>

        <div class="detail-row">
            <strong>Address:</strong>
            <?php echo htmlspecialchars($resident['address'] ?? 'Not provided'); ?>
        </div>

    </div>


    <!-- Academic Information -->

    <div class="details-card">

        <h2>Academic Information</h2>

        <div class="detail-row">
            <strong>Course:</strong>
            <?php echo htmlspecialchars($resident['course'] ?? 'Not provided'); ?>
        </div>

        <div class="detail-row">
            <strong>Year of Study:</strong>
            <?php echo htmlspecialchars($resident['year_of_study'] ?? 'Not provided'); ?>
        </div>

    </div>


    <!-- Application Information -->

    <div class="details-card">

        <h2>Accommodation Application</h2>

        <div class="detail-row">

            <strong>Application Status:</strong>

            <?php

            $status = $resident['status'];

            if ($status === 'Approved') {
                echo '<span class="approved">Approved</span>';
            } elseif ($status === 'Pending') {
                echo '<span class="pending">Pending</span>';
            } elseif ($status === 'Rejected') {
                echo '<span class="rejected">Rejected</span>';
            } else {
                echo 'Not Applied';
            }

            ?>

        </div>

        <div class="detail-row">
            <strong>Requested Room Type:</strong>
            <?php echo htmlspecialchars($resident['room_type'] ?? 'Not selected'); ?>
        </div>

    </div>


    <!-- Room Allocation Information -->

    <div class="details-card">

        <h2>Room Allocation Details</h2>

        <?php if (!empty($resident['room_number'])) { ?>

            <div class="detail-row">
                <strong>Room Number:</strong>
                <span class="room-allocated">
                    <?php echo htmlspecialchars($resident['room_number']); ?>
                </span>
            </div>

            <div class="detail-row">
                <strong>Allocated Room Type:</strong>
                <?php echo htmlspecialchars($resident['allocated_room_type']); ?>
            </div>

            <div class="detail-row">
                <strong>Room Capacity:</strong>
                <?php echo (int)$resident['capacity']; ?> resident(s)
            </div>

        <?php } else { ?>

            <p class="not-allocated">
                No room has been allocated to this resident yet.
            </p>

        <?php } ?>

    </div>


    <!-- Roommates -->

    <div class="details-card">

        <h2>Roommates</h2>

        <?php if (!empty($resident['room_number'])) { ?>

            <?php if (count($roommates) > 0) { ?>

                <?php foreach ($roommates as $roommate) { ?>

                    <div class="detail-row">

                        <strong>Name:</strong>
                        <?php echo htmlspecialchars($roommate['fullname']); ?>

                        <br>

                        <strong>Email:</strong>
                        <?php echo htmlspecialchars($roommate['email']); ?>

                    </div>

                <?php } ?>

            <?php } else { ?>

                <p>No other residents are sharing this room.</p>

            <?php } ?>

        <?php } else { ?>

            <p>Roommates will appear after room allocation.</p>

        <?php } ?>

    </div>


    <!-- Action Buttons -->

    <div class="action-buttons">

        <button onclick="window.print()">
            Print Resident Details
        </button>

        <a href="view_residents.php">
            Back to Residents
        </a>

        <a href="admin_dashboard.php">
            Admin Dashboard
        </a>

    </div>

</div>

</body>
</html>
