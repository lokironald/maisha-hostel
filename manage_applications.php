
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

// Update application status
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_status'])) {

    // Verify CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)) {

        http_response_code(403);
        exit("Invalid security token. Please refresh the page and try again.");
    }

    $application_id = (int)($_POST['application_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    if (
        $application_id <= 0 ||
        !in_array($new_status, ['Approved', 'Rejected'], true)
    ) {
        $message = "Invalid application details.";
        $messageType = "error";

    } else {

        try {
            mysqli_begin_transaction($conn);

            // Lock application record
            $stmt = mysqli_prepare(
                $conn,
                "SELECT status FROM applications
                 WHERE id = ? FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "i", $application_id);
            mysqli_stmt_execute($stmt);

            $applicationResult = mysqli_stmt_get_result($stmt);
            $application = mysqli_fetch_assoc($applicationResult);

            mysqli_stmt_close($stmt);

            if (!$application) {
                throw new Exception("Application not found.");
            }

            $old_status = $application['status'];

            if ($old_status === $new_status) {

                $message = "This application already has that status.";
                $messageType = "info";

            } else {

                // Release room if applicant is rejected
                if ($new_status === 'Rejected') {

                    $deleteAllocation = mysqli_prepare(
                        $conn,
                        "DELETE FROM room_allocations
                         WHERE application_id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $deleteAllocation,
                        "i",
                        $application_id
                    );

                    mysqli_stmt_execute($deleteAllocation);
                    mysqli_stmt_close($deleteAllocation);
                }

                // Update application status
                $updateApplication = mysqli_prepare(
                    $conn,
                    "UPDATE applications
                     SET status = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $updateApplication,
                    "si",
                    $new_status,
                    $application_id
                );

                mysqli_stmt_execute($updateApplication);
                mysqli_stmt_close($updateApplication);

                $message = "Application status updated successfully!";
                $messageType = "success";
            }

            mysqli_commit($conn);

        } catch (Throwable $error) {

            mysqli_rollback($conn);

            // Record technical error privately
            error_log(
                "Application update error: " . $error->getMessage()
            );

            $message = "Something went wrong while updating the application. Please try again.";
            $messageType = "error";
        }
    }
}

// Retrieve all applications and resident details
$sql = "SELECT
            a.id,
            a.user_id,
            a.gender,
            a.phone,
            a.course,
            a.year_of_study,
            a.room_type,
            a.address,
            a.status,

            u.fullname,
            u.email,

            hr.room_number,
            hr.room_type AS allocated_room_type,
            hr.capacity AS room_capacity

        FROM applications a

        INNER JOIN users u
            ON a.user_id = u.id

        LEFT JOIN room_allocations ra
            ON a.id = ra.application_id

        LEFT JOIN hostel_rooms hr
            ON ra.room_id = hr.id

        ORDER BY a.id DESC";

$result = mysqli_query($conn, $sql);

// Application statistics
$stats_sql = "SELECT
                COUNT(*) AS total,
                SUM(status = 'Pending') AS pending,
                SUM(status = 'Approved') AS approved,
                SUM(status = 'Rejected') AS rejected
              FROM applications";

$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Applications - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .applications-container {
            max-width: 1150px;
            margin: 30px auto;
            padding: 20px;
        }

        .application-card {
            background: white;
            padding: 22px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .application-card h3 {
            color: #198754;
        }

        .application-card p {
            padding: 7px;
            border-bottom: 1px solid #eee;
        }

        .application-card select {
            padding: 10px;
            margin: 10px;
            border-radius: 5px;
        }

        .application-card button {
            background: #198754;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            cursor: pointer;
        }

        .application-card button:hover {
            background: #146c43;
        }

        .allocate-btn {
            display: inline-block;
            background: #0d6efd;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 12px;
        }

        .allocate-btn:hover {
            background: #084298;
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

        .info-message {
            background: #cff4fc;
            color: #055160;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .status-approved {
            color: green;
            font-weight: bold;
        }

        .status-rejected {
            color: red;
            font-weight: bold;
        }

        .status-pending {
            color: orange;
            font-weight: bold;
        }

        .allocation-status {
            background: #f4f7f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .back-link {
            display: inline-block;
            margin: 15px 10px 15px 0;
            color: #198754;
            text-decoration: none;
            font-weight: bold;
        }

        .statistics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            background: white;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #198754;
        }

        .stat-card h3 {
            font-size: 15px;
            color: #555;
        }

        .stat-card p {
            font-size: 25px;
            font-weight: bold;
            color: #198754;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .applications-container {
                max-width: 100%;
            }

            .application-card {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
        }

    </style>

</head>

<body>

<div class="applications-container">

    <h1>Manage Resident Applications</h1>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?>!
    </p>

    <!-- APPLICATION STATISTICS -->

    <h2>Application Overview</h2>

    <section class="statistics">

        <div class="stat-card">
            <h3>Total Applications</h3>
            <p><?php echo (int)$stats['total']; ?></p>
        </div>

        <div class="stat-card">
            <h3>Pending</h3>
            <p><?php echo (int)$stats['pending']; ?></p>
        </div>

        <div class="stat-card">
            <h3>Approved</h3>
            <p><?php echo (int)$stats['approved']; ?></p>
        </div>

        <div class="stat-card">
            <h3>Rejected</h3>
            <p><?php echo (int)$stats['rejected']; ?></p>
        </div>

    </section>

    <!-- DISPLAY MESSAGES -->

    <?php if ($message !== "") { ?>

        <div class="<?php echo htmlspecialchars($messageType); ?>-message">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>

    <h2>All Accommodation Applications</h2>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <?php while ($application = mysqli_fetch_assoc($result)) { ?>

            <div class="application-card">

                <hr>

                <h3>
                    Resident:
                    <?php echo htmlspecialchars($application['fullname']); ?>
                </h3>

                <p>
                    <strong>Application ID:</strong>
                    <?php echo (int)$application['id']; ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($application['email']); ?>
                </p>

                <p>
                    <strong>Gender:</strong>
                    <?php echo htmlspecialchars($application['gender'] ?? 'Not provided'); ?>
                </p>

                <p>
                    <strong>Phone:</strong>
                    <?php echo htmlspecialchars($application['phone'] ?? 'Not provided'); ?>
                </p>

                <p>
                    <strong>Course:</strong>
                    <?php echo htmlspecialchars($application['course'] ?? 'Not provided'); ?>
                </p>

                <p>
                    <strong>Year of Study:</strong>
                    <?php echo htmlspecialchars($application['year_of_study'] ?? 'Not provided'); ?>
                </p>

                <p>
                    <strong>Requested Room Type:</strong>
                    <?php echo htmlspecialchars($application['room_type'] ?? 'Not selected'); ?>
                </p>

                <p>
                    <strong>Address:</strong>
                    <?php echo htmlspecialchars($application['address'] ?? 'Not provided'); ?>
                </p>

                <!-- APPLICATION STATUS -->

                <p>
                    <strong>Current Status:</strong>

                    <?php
                    $statusClass = strtolower($application['status']);
                    ?>

                    <span class="status-<?php echo htmlspecialchars($statusClass); ?>">

                        <?php echo htmlspecialchars($application['status']); ?>

                    </span>

                </p>

                <!-- ROOM ALLOCATION INFORMATION -->

                <div class="allocation-status">

                    <h3>Room Allocation Information</h3>

                    <?php if (!empty($application['room_number'])) { ?>

                        <p>
                            <strong>Allocated Room:</strong>
                            <?php echo htmlspecialchars($application['room_number']); ?>
                        </p>

                        <p>
                            <strong>Allocated Room Type:</strong>
                            <?php echo htmlspecialchars($application['allocated_room_type']); ?>
                        </p>

                        <p>
                            <strong>Room Capacity:</strong>
                            <?php echo (int)$application['room_capacity']; ?> Students
                        </p>

                        <p class="status-approved">
                            Room successfully assigned!
                        </p>

                    <?php } elseif ($application['status'] === 'Approved') { ?>

                        <p style="color:orange; font-weight:bold;">
                            Approved - Awaiting Room Allocation
                        </p>

                    <?php } elseif ($application['status'] === 'Rejected') { ?>

                        <p style="color:red;">
                            Application rejected. No room allocated.
                        </p>

                    <?php } else { ?>

                        <p style="color:orange;">
                            Waiting for application approval.
                        </p>

                    <?php } ?>

                </div>

                <!-- UPDATE APPLICATION STATUS -->

                <div class="no-print">

                    <form method="POST"
                          onsubmit="return confirm('Are you sure you want to update this application?');">

                        <!-- CSRF SECURITY TOKEN -->

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                        >

                        <input
                            type="hidden"
                            name="application_id"
                            value="<?php echo (int)$application['id']; ?>"
                        >

                        <label>
                            <strong>Change Application Status:</strong>
                        </label>

                        <select name="status" required>

                            <option value="">Select Status</option>

                            <option value="Approved">Approve</option>

                            <option value="Rejected">Reject</option>

                        </select>

                        <button type="submit" name="update_status">
                            Update Status
                        </button>

                    </form>

                    <!-- ALLOCATE ROOM BUTTON -->

                    <?php if (
                        $application['status'] === 'Approved' &&
                        empty($application['room_number'])
                    ) { ?>

                        <a
                            href="allocate_room.php?id=<?php echo (int)$application['id']; ?>"
                            class="allocate-btn"
                        >
                            Allocate Room
                        </a>

                    <?php } ?>

                </div>

            </div>

        <?php } ?>

    <?php } else { ?>

        <p>No accommodation applications have been submitted yet.</p>

    <?php } ?>

    <div class="no-print">

        <a href="dashboard.php" class="back-link">
            Back to Admin Dashboard
        </a>

        <a href="room_occupancy.php" class="back-link">
            View Room Occupancy
        </a>

        <a href="admin_logout.php" class="back-link">
            Admin Logout
        </a>

    </div>

</div>

</body>
</html>
