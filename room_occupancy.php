
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Enable secure MySQLi error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Retrieve all rooms and their occupancy
$rooms = [];

$totalRooms = 0;
$totalOccupiedRooms = 0;
$totalAvailableRooms = 0;
$totalStudents = 0;
$fullRooms = 0;

$errorMessage = "";

try {

    $sql = "SELECT
                hr.id,
                hr.room_number,
                hr.room_type,
                hr.capacity,
                COUNT(ra.id) AS occupied,
                GROUP_CONCAT(
                    u.fullname
                    ORDER BY u.fullname
                    SEPARATOR ', '
                ) AS residents

            FROM hostel_rooms hr

            LEFT JOIN room_allocations ra
            ON hr.id = ra.room_id

            LEFT JOIN applications a
            ON ra.application_id = a.id

            LEFT JOIN users u
            ON a.user_id = u.id

            GROUP BY
                hr.id,
                hr.room_number,
                hr.room_type,
                hr.capacity

            ORDER BY hr.id ASC";

    $result = mysqli_query($conn, $sql);

    while ($room = mysqli_fetch_assoc($result)) {

        $room['occupied'] = (int)$room['occupied'];
        $room['capacity'] = (int)$room['capacity'];

        $room['available_spaces'] = max(
            0,
            $room['capacity'] - $room['occupied']
        );

        if ($room['occupied'] >= $room['capacity']) {
            $room['status'] = "Full";
            $fullRooms++;
        } else {
            $room['status'] = "Available";
            $totalAvailableRooms++;
        }

        if ($room['occupied'] > 0) {
            $totalOccupiedRooms++;
        }

        $totalStudents += $room['occupied'];
        $totalRooms++;

        $rooms[] = $room;
    }

} catch (Exception $e) {

    // Keep technical errors out of the browser
    error_log("Room Occupancy Error: " . $e->getMessage());

    $errorMessage = "Unable to retrieve room information. Please try again later.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Room Occupancy - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .occupancy-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .occupancy-container h1 {
            color: #198754;
            text-align: center;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin: 25px 0;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            text-align: center;
        }

        .summary-card h3 {
            color: #555;
            font-size: 15px;
        }

        .summary-card p {
            color: #198754;
            font-size: 28px;
            font-weight: bold;
        }

        .room-table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
        }

        .room-table {
            width: 100%;
            border-collapse: collapse;
        }

        .room-table th,
        .room-table td {
            padding: 13px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .room-table th {
            background: #198754;
            color: white;
        }

        .room-table tr:nth-child(even) {
            background: #f2f2f2;
        }

        .available {
            color: green;
            font-weight: bold;
        }

        .full {
            color: red;
            font-weight: bold;
        }

        .error-message {
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            margin-right: 8px;
            padding: 10px 15px;
            background: #198754;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-link:hover {
            background: #146c43;
        }

        @media print {

            .back-link,
            .print-button {
                display: none;
            }

            .occupancy-container {
                margin: 0;
            }

        }

    </style>

</head>

<body>

<div class="occupancy-container">

    <h1>Maisha Hostel Room Occupancy</h1>

    <p style="text-align:center;">
        Welcome,
        <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?>!
    </p>

    <?php if ($errorMessage !== "") { ?>

        <div class="error-message">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>

    <?php } else { ?>

        <!-- ROOM STATISTICS -->

        <section class="summary-cards">

            <div class="summary-card">
                <h3>Total Rooms</h3>
                <p><?php echo $totalRooms; ?></p>
            </div>

            <div class="summary-card">
                <h3>Occupied Rooms</h3>
                <p><?php echo $totalOccupiedRooms; ?></p>
            </div>

            <div class="summary-card">
                <h3>Available Rooms</h3>
                <p><?php echo $totalAvailableRooms; ?></p>
            </div>

            <div class="summary-card">
                <h3>Full Rooms</h3>
                <p><?php echo $fullRooms; ?></p>
            </div>

            <div class="summary-card">
                <h3>Total Students</h3>
                <p><?php echo $totalStudents; ?></p>
            </div>

        </section>

        <!-- ROOM DETAILS TABLE -->

        <div class="room-table-container">

            <h2>All Hostel Rooms</h2>

            <table class="room-table">

                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Room Type</th>
                        <th>Capacity</th>
                        <th>Occupied</th>
                        <th>Available Spaces</th>
                        <th>Residents</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($totalRooms > 0) { ?>

                        <?php foreach ($rooms as $room) { ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($room['room_number']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($room['room_type']); ?>
                                </td>

                                <td>
                                    <?php echo $room['capacity']; ?>
                                </td>

                                <td>
                                    <?php echo $room['occupied']; ?>
                                </td>

                                <td>
                                    <?php echo $room['available_spaces']; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $room['residents'] ?? 'No residents'
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ($room['status'] === 'Full') { ?>

                                        <span class="full">Full</span>

                                    <?php } else { ?>

                                        <span class="available">Available</span>

                                    <?php } ?>

                                </td>

                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>
                            <td colspan="7">
                                No hostel rooms have been registered.
                            </td>
                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

        <br>

        <button type="button"
                class="print-button"
                onclick="window.print()">
            Print Room Report
        </button>

    <?php } ?>

    <br>

    <a href="dashboard.php" class="back-link">
        Back to Admin Dashboard
    </a>

    <a href="manage_applications.php" class="back-link">
        Manage Applications
    </a>

</div>

</body>
</html>
