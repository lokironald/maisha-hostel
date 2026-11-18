
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message = "";
$messageType = "";

// Generate CSRF security token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$application_id = (int)($_GET['id'] ?? $_POST['application_id'] ?? 0);

if ($application_id <= 0) {
    die("Invalid application ID.");
}

// Process room allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        http_response_code(403);
        exit("Invalid security token. Please refresh the page and try again.");
    }

    try {

        mysqli_begin_transaction($conn);

        // Lock application record
        $sql = "SELECT a.id, a.user_id, a.room_type,
                       a.status, u.fullname
                FROM applications a
                INNER JOIN users u ON a.user_id = u.id
                WHERE a.id = ?
                FOR UPDATE";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $application = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$application) {
            throw new Exception("Application not found.");
        }

        if ($application['status'] !== 'Approved') {
            throw new Exception(
                "Only approved applicants can receive rooms."
            );
        }

        // Check existing allocation
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM room_allocations
             WHERE application_id = ?"
        );

        mysqli_stmt_bind_param($check, "i", $application_id);
        mysqli_stmt_execute($check);

        $existing = mysqli_stmt_get_result($check);
        $alreadyAllocated = mysqli_fetch_assoc($existing);

        mysqli_stmt_close($check);

        if ($alreadyAllocated) {
            throw new Exception(
                "This applicant already has an allocated room."
            );
        }

        // Lock matching rooms
        $roomType = $application['room_type'];

        $roomSql = "SELECT id, room_number, room_type, capacity
                    FROM hostel_rooms
                    WHERE room_type = ?
                    ORDER BY id ASC
                    FOR UPDATE";

        $roomStmt = mysqli_prepare($conn, $roomSql);
        mysqli_stmt_bind_param($roomStmt, "s", $roomType);
        mysqli_stmt_execute($roomStmt);

        $rooms = mysqli_stmt_get_result($roomStmt);

        $availableRoom = null;

        while ($room = mysqli_fetch_assoc($rooms)) {

            $countStmt = mysqli_prepare(
                $conn,
                "SELECT COUNT(*) AS occupied
                 FROM room_allocations
                 WHERE room_id = ?"
            );

            mysqli_stmt_bind_param(
                $countStmt,
                "i",
                $room['id']
            );

            mysqli_stmt_execute($countStmt);

            $countResult = mysqli_stmt_get_result($countStmt);
            $count = mysqli_fetch_assoc($countResult);

            mysqli_stmt_close($countStmt);

            if (
                (int)$count['occupied'] <
                (int)$room['capacity']
            ) {
                $availableRoom = $room;
                break;
            }
        }

        mysqli_stmt_close($roomStmt);

        if (!$availableRoom) {
            throw new Exception(
                "No available rooms of this type. All matching rooms are full."
            );
        }

        // Insert room allocation
        $insert = mysqli_prepare(
            $conn,
            "INSERT INTO room_allocations
             (application_id, room_id)
             VALUES (?, ?)"
        );

        mysqli_stmt_bind_param(
            $insert,
            "ii",
            $application_id,
            $availableRoom['id']
        );

        mysqli_stmt_execute($insert);
        mysqli_stmt_close($insert);

        // Save transaction
        mysqli_commit($conn);

        $message = "Room successfully allocated! " .
                   $availableRoom['room_number'] .
                   " assigned to " . $application['fullname'] . ".";

        $messageType = "success";

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        // Keep technical errors in server logs
        error_log(
            "Room allocation error: " . $e->getMessage()
        );

        // Display safe error message
        if ($e->getMessage() === "Application not found.") {
            $message = "Application not found.";
        } elseif (
            $e->getMessage() ===
            "Only approved applicants can receive rooms."
        ) {
            $message = $e->getMessage();
        } elseif (
            $e->getMessage() ===
            "This applicant already has an allocated room."
        ) {
            $message = $e->getMessage();
        } elseif (
            $e->getMessage() ===
            "No available rooms of this type. All matching rooms are full."
        ) {
            $message = $e->getMessage();
        } else {
            $message = "Room allocation failed. Please try again.";
        }

        $messageType = "error";
    }
}

// Retrieve applicant details
$sql = "SELECT
            a.id,
            a.room_type,
            a.status,
            u.fullname,
            u.email
        FROM applications a
        INNER JOIN users u ON a.user_id = u.id
        WHERE a.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $application_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$applicant = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Check existing allocation
$allocation = null;

if ($applicant) {

    $sql = "SELECT
                hr.room_number,
                hr.room_type,
                hr.capacity
            FROM room_allocations ra
            INNER JOIN hostel_rooms hr
                ON ra.room_id = hr.id
            WHERE ra.application_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $application_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $allocation = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
}

// Get room availability for requested room type
$roomAvailability = [];

if ($applicant) {

    $sql = "SELECT
                hr.room_number,
                hr.room_type,
                hr.capacity,
                COUNT(ra.id) AS occupied
            FROM hostel_rooms hr
            LEFT JOIN room_allocations ra
                ON hr.id = ra.room_id
            WHERE hr.room_type = ?
            GROUP BY
                hr.id,
                hr.room_number,
                hr.room_type,
                hr.capacity
            ORDER BY hr.id ASC";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $applicant['room_type']
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {

        $row['available'] =
            (int)$row['capacity'] - (int)$row['occupied'];

        $roomAvailability[] = $row;
    }

    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Room Allocation - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.allocation-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 25px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.allocation-container h1,
.allocation-container h2 {
    color: #198754;
}

.info-box {
    background: #f4f7f5;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-box p {
    padding: 8px;
    border-bottom: 1px solid #ddd;
}

.availability-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.availability-table th,
.availability-table td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.availability-table th {
    background: #198754;
    color: white;
}

.available-status {
    color: green;
    font-weight: bold;
}

.full-status {
    color: red;
    font-weight: bold;
}

.allocate-btn {
    background: #198754;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.allocate-btn:hover {
    background: #146c43;
}

.success-message {
    background: #d1e7dd;
    color: #0f5132;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.error-message {
    background: #f8d7da;
    color: #842029;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #198754;
    text-decoration: none;
}

.table-container {
    overflow-x: auto;
}

</style>

</head>

<body>

<div class="allocation-container">

    <h1>Room Allocation Management</h1>

    <?php if ($message !== "") { ?>

        <div class="<?php echo $messageType === 'success' ? 'success-message' : 'error-message'; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>

    <?php if (!$applicant) { ?>

        <p>Applicant not found.</p>

    <?php } else { ?>

        <h2>Applicant Information</h2>

        <div class="info-box">

            <p>
                <strong>Application ID:</strong>
                <?php echo (int)$applicant['id']; ?>
            </p>

            <p>
                <strong>Full Name:</strong>
                <?php echo htmlspecialchars($applicant['fullname']); ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo htmlspecialchars($applicant['email']); ?>
            </p>

            <p>
                <strong>Requested Room:</strong>
                <?php echo htmlspecialchars($applicant['room_type']); ?>
            </p>

            <p>
                <strong>Application Status:</strong>
                <?php echo htmlspecialchars($applicant['status']); ?>
            </p>

        </div>

        <h2>Room Availability</h2>

        <?php if (count($roomAvailability) > 0) { ?>

            <div class="table-container">

                <table class="availability-table">

                    <tr>
                        <th>Room Number</th>
                        <th>Room Type</th>
                        <th>Capacity</th>
                        <th>Occupied</th>
                        <th>Available Spaces</th>
                        <th>Status</th>
                    </tr>

                    <?php foreach ($roomAvailability as $room) { ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($room['room_number']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($room['room_type']); ?>
                            </td>

                            <td>
                                <?php echo (int)$room['capacity']; ?>
                            </td>

                            <td>
                                <?php echo (int)$room['occupied']; ?>
                            </td>

                            <td>
                                <?php echo (int)$room['available']; ?>
                            </td>

                            <td>

                                <?php if ($room['available'] > 0) { ?>

                                    <span class="available-status">
                                        Available
                                    </span>

                                <?php } else { ?>

                                    <span class="full-status">
                                        Full
                                    </span>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                </table>

            </div>

        <?php } else { ?>

            <div class="error-message">
                No rooms of the requested type have been registered.
            </div>

        <?php } ?>

        <h2>Room Assignment</h2>

        <?php if ($allocation) { ?>

            <div class="success-message">

                <h3>Room Already Allocated</h3>

                <p>
                    <strong>Room Number:</strong>
                    <?php echo htmlspecialchars($allocation['room_number']); ?>
                </p>

                <p>
                    <strong>Room Type:</strong>
                    <?php echo htmlspecialchars($allocation['room_type']); ?>
                </p>

                <p>
                    <strong>Room Capacity:</strong>
                    <?php echo (int)$allocation['capacity']; ?> student(s).
                </p>

            </div>

        <?php } elseif ($applicant['status'] === 'Approved') { ?>

            <p>
                The system will automatically select the first
                available room matching the applicant's request.
            </p>

            <form method="POST"
                  onsubmit="return confirm('Are you sure you want to allocate a room to this applicant?');">

                <!-- CSRF SECURITY TOKEN -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                >

                <input
                    type="hidden"
                    name="application_id"
                    value="<?php echo (int)$application_id; ?>"
                >

                <button type="submit" class="allocate-btn">
                    Allocate Room
                </button>

            </form>

        <?php } else { ?>

            <div class="error-message">
                This applicant must be approved before room allocation.
            </div>

        <?php } ?>

    <?php } ?>

    <br>

    <a href="manage_applications.php" class="back-link">
        Back to Manage Applications
    </a>

    <br><br>

    <a href="dashboard.php" class="back-link">
        Admin Dashboard
    </a>

</div>

</body>
</html>
