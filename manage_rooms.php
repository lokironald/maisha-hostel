
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

// Update room information
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_room'])) {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {

        $message = "Invalid security token. Please refresh the page and try again.";
        $messageType = "error";

    } else {

        $room_id = (int)($_POST['room_id'] ?? 0);
        $total_rooms = filter_var(
            $_POST['total_rooms'] ?? null,
            FILTER_VALIDATE_INT
        );
        $price = filter_var(
            $_POST['price'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $room_id <= 0 ||
            $total_rooms === false ||
            $total_rooms === null ||
            $price === false ||
            $price === null ||
            $total_rooms < 0 ||
            $price < 0
        ) {

            $message = "Invalid room information!";
            $messageType = "error";

        } else {

            try {

                mysqli_begin_transaction($conn);

                $check = mysqli_prepare(
                    $conn,
                    "SELECT occupied_rooms
                     FROM rooms
                     WHERE id = ?
                     FOR UPDATE"
                );

                mysqli_stmt_bind_param($check, "i", $room_id);
                mysqli_stmt_execute($check);

                $checkResult = mysqli_stmt_get_result($check);
                $currentRoom = mysqli_fetch_assoc($checkResult);

                mysqli_stmt_close($check);

                if (!$currentRoom) {

                    mysqli_rollback($conn);

                    $message = "Room type not found.";
                    $messageType = "error";

                } elseif (
                    $total_rooms < (int)$currentRoom['occupied_rooms']
                ) {

                    mysqli_rollback($conn);

                    $message = "Total rooms cannot be less than occupied rooms.";
                    $messageType = "error";

                } else {

                    $update = mysqli_prepare(
                        $conn,
                        "UPDATE rooms
                         SET total_rooms = ?, price = ?
                         WHERE id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $update,
                        "iii",
                        $total_rooms,
                        $price,
                        $room_id
                    );

                    mysqli_stmt_execute($update);
                    mysqli_stmt_close($update);

                    mysqli_commit($conn);

                    $message = "Room information updated successfully!";
                    $messageType = "success";

                    // Generate a fresh token after successful update
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }

            } catch (Exception $e) {

                mysqli_rollback($conn);

                error_log(
                    "Manage Rooms Update Error: " . $e->getMessage()
                );

                $message = "Unable to update room information. Please try again.";
                $messageType = "error";
            }
        }
    }
}

// Retrieve room type information
$rooms = [];
$totalRooms = 0;
$occupiedRooms = 0;
$availableRooms = 0;

// Retrieve individual numbered rooms
$numberedRooms = [];
$totalSpaces = 0;
$occupiedSpaces = 0;
$availableSpaces = 0;

try {

    $result = mysqli_query(
        $conn,
        "SELECT * FROM rooms ORDER BY id ASC"
    );

    while ($room = mysqli_fetch_assoc($result)) {

        $room['total_rooms'] = (int)$room['total_rooms'];
        $room['occupied_rooms'] = (int)$room['occupied_rooms'];

        $room['available_rooms'] = max(
            0,
            $room['total_rooms'] - $room['occupied_rooms']
        );

        $rooms[] = $room;

        $totalRooms += $room['total_rooms'];
        $occupiedRooms += $room['occupied_rooms'];
        $availableRooms += $room['available_rooms'];
    }

    $numberedRoomsResult = mysqli_query(
        $conn,
        "SELECT
            hr.id,
            hr.room_number,
            hr.room_type,
            hr.capacity,
            COUNT(ra.id) AS occupied
         FROM hostel_rooms hr
         LEFT JOIN room_allocations ra
            ON hr.id = ra.room_id
         GROUP BY
            hr.id,
            hr.room_number,
            hr.room_type,
            hr.capacity
         ORDER BY hr.id ASC"
    );

    while ($room = mysqli_fetch_assoc($numberedRoomsResult)) {

        $room['capacity'] = (int)$room['capacity'];
        $room['occupied'] = (int)$room['occupied'];

        $room['available'] = max(
            0,
            $room['capacity'] - $room['occupied']
        );

        $numberedRooms[] = $room;

        $totalSpaces += $room['capacity'];
        $occupiedSpaces += $room['occupied'];
        $availableSpaces += $room['available'];
    }

} catch (Exception $e) {

    error_log("Manage Rooms Retrieval Error: " . $e->getMessage());

    $message = "Unable to retrieve room information. Please try again later.";
    $messageType = "error";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Rooms - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .room-dashboard {
            max-width: 1150px;
            margin: 30px auto;
            padding: 20px;
        }

        .room-dashboard h1 {
            color: #198754;
        }

        .room-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .summary-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .summary-card h3 {
            color: #198754;
        }

        .summary-card p {
            font-size: 27px;
            font-weight: bold;
        }

        .room-card {
            background: white;
            padding: 24px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .room-card input {
            padding: 10px;
            width: 100%;
            max-width: 350px;
            margin: 8px 0;
            box-sizing: border-box;
        }

        .room-button {
            background: #198754;
            color: white;
            padding: 11px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .room-button:hover {
            background: #146c43;
        }

        .available-status {
            color: green;
            font-weight: bold;
        }

        .full-status {
            color: red;
            font-weight: bold;
        }

        .room-message {
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .success {
            background: #e8f5e9;
            color: #146c43;
        }

        .error {
            background: #f8d7da;
            color: #842029;
        }

        .numbered-room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
        }

        .numbered-room {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            border-left: 5px solid #198754;
        }

        .numbered-room.full {
            border-left-color: #dc3545;
        }

        .section-heading {
            margin-top: 35px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }

    </style>

</head>

<body>

<div class="room-dashboard">

    <h1>Maisha Hostel Room Management</h1>

    <p>
        Welcome,
        <?php echo htmlspecialchars(
            $_SESSION['admin_name'] ?? 'Administrator'
        ); ?>!
    </p>

    <?php if ($message !== "") { ?>

        <div class="room-message <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>

    <h2>Room Type Statistics</h2>

    <div class="room-summary">

        <div class="summary-card">
            <h3>Total Rooms</h3>
            <p><?php echo $totalRooms; ?></p>
        </div>

        <div class="summary-card">
            <h3>Occupied Rooms</h3>
            <p><?php echo $occupiedRooms; ?></p>
        </div>

        <div class="summary-card">
            <h3>Available Rooms</h3>
            <p><?php echo $availableRooms; ?></p>
        </div>

    </div>

    <h2 class="section-heading">
        Manage Room Types and Prices
    </h2>

    <?php if (count($rooms) > 0) { ?>

        <?php foreach ($rooms as $room) { ?>

            <div class="room-card">

                <h3>
                    <?php echo htmlspecialchars($room['room_type']); ?>
                </h3>

                <p>
                    <strong>Price per semester:</strong>
                    UGX <?php echo number_format($room['price']); ?>
                </p>

                <p>
                    <strong>Total Rooms:</strong>
                    <?php echo $room['total_rooms']; ?>
                </p>

                <p>
                    <strong>Occupied Rooms:</strong>
                    <?php echo $room['occupied_rooms']; ?>
                </p>

                <p>
                    <strong>Available Rooms:</strong>
                    <?php echo $room['available_rooms']; ?>
                </p>

                <p class="<?php echo $room['available_rooms'] > 0
                    ? 'available-status'
                    : 'full-status'; ?>">

                    Status:
                    <?php echo $room['available_rooms'] > 0
                        ? 'Available'
                        : 'FULL'; ?>

                </p>

                <hr>

                <h4>Update Room Information</h4>

                <form method="POST">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?php echo htmlspecialchars(
                               $_SESSION['csrf_token']
                           ); ?>">

                    <input type="hidden"
                           name="room_id"
                           value="<?php echo $room['id']; ?>">

                    <label>Total Rooms:</label><br>

                    <input type="number"
                           name="total_rooms"
                           min="<?php echo $room['occupied_rooms']; ?>"
                           value="<?php echo $room['total_rooms']; ?>"
                           required>

                    <br>

                    <label>Price per Semester (UGX):</label><br>

                    <input type="number"
                           name="price"
                           min="0"
                           value="<?php echo (int)$room['price']; ?>"
                           required>

                    <br><br>

                    <button type="submit"
                            name="update_room"
                            class="room-button">
                        Update Room Information
                    </button>

                </form>

            </div>

        <?php } ?>

    <?php } else { ?>

        <p>No room type records found.</p>

    <?php } ?>


    <h2 class="section-heading">
        Individual Numbered Rooms
    </h2>

    <p>
        These are the actual rooms used when allocating accommodation
        to approved residents.
    </p>

    <div class="room-summary">

        <div class="summary-card">
            <h3>Total Bed Spaces</h3>
            <p><?php echo $totalSpaces; ?></p>
        </div>

        <div class="summary-card">
            <h3>Occupied Spaces</h3>
            <p><?php echo $occupiedSpaces; ?></p>
        </div>

        <div class="summary-card">
            <h3>Available Spaces</h3>
            <p><?php echo $availableSpaces; ?></p>
        </div>

    </div>

    <div class="numbered-room-grid">

        <?php if (count($numberedRooms) > 0) { ?>

            <?php foreach ($numberedRooms as $room) { ?>

                <div class="numbered-room
                    <?php echo $room['available'] == 0 ? 'full' : ''; ?>">

                    <h3>
                        <?php echo htmlspecialchars($room['room_number']); ?>
                    </h3>

                    <p>
                        <strong>Type:</strong>
                        <?php echo htmlspecialchars($room['room_type']); ?>
                    </p>

                    <p>
                        <strong>Capacity:</strong>
                        <?php echo $room['capacity']; ?>
                    </p>

                    <p>
                        <strong>Occupied:</strong>
                        <?php echo $room['occupied']; ?>
                    </p>

                    <p>
                        <strong>Available spaces:</strong>
                        <?php echo $room['available']; ?>
                    </p>

                    <p class="<?php echo $room['available'] > 0
                        ? 'available-status'
                        : 'full-status'; ?>">

                        <?php echo $room['available'] > 0
                            ? 'Available'
                            : 'FULL'; ?>

                    </p>

                </div>

            <?php } ?>

        <?php } else { ?>

            <p>No numbered rooms have been registered yet.</p>

        <?php } ?>

    </div>

    <br>

    <div class="no-print">

        <button type="button"
                onclick="window.print()"
                class="room-button">
            Print Room Report
        </button>

        <br><br>

        <a href="room_occupancy.php">
            View Full Room Occupancy Report
        </a>

        <br><br>

        <a href="dashboard.php">
            Back to Admin Dashboard
        </a>

        <br><br>

        <a href="admin_logout.php">
            Admin Logout
        </a>

    </div>

</div>

</body>
</html>
