
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get resident statistics
$statsQuery = mysqli_query($conn, "
    SELECT
        (SELECT COUNT(*) FROM users) AS total_residents,
        (SELECT COUNT(*) FROM applications
         WHERE status = 'Approved') AS approved,
        (SELECT COUNT(*) FROM applications
         WHERE status = 'Pending') AS pending,
        (SELECT COUNT(*) FROM applications
         WHERE status = 'Rejected') AS rejected,
        (SELECT COUNT(*) FROM room_allocations) AS allocated
");

if (!$statsQuery) {
    die("Database error: " . htmlspecialchars(mysqli_error($conn)));
}

$stats = mysqli_fetch_assoc($statsQuery);

// Retrieve resident report
$sql = "
    SELECT
        u.id,
        u.fullname,
        u.email,
        a.phone,
        a.course,
        a.year_of_study,
        a.status,
        hr.room_number,
        hr.room_type

    FROM users u

    LEFT JOIN applications a
        ON u.id = a.user_id

    LEFT JOIN room_allocations ra
        ON a.id = ra.application_id

    LEFT JOIN hostel_rooms hr
        ON ra.room_id = hr.id

    ORDER BY u.id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database error: " . htmlspecialchars(mysqli_error($conn)));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Resident Report - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .report-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .report-heading {
            text-align: center;
            color: #198754;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .summary-card h3 {
            color: #198754;
        }

        .summary-card p {
            font-size: 27px;
            font-weight: bold;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #198754;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
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

        .report-button {
            background: #198754;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .report-button:hover {
            background: #146c43;
        }

        @media print {

            .no-print {
                display: none;
            }

            .report-container {
                margin: 0;
                max-width: 100%;
            }

            body {
                background: white;
            }

            table {
                font-size: 10px;
            }

        }

    </style>

</head>

<body>

<div class="report-container">

    <h1 class="report-heading">
        MAisha Hostel
    </h1>

    <h2 class="report-heading">
        Residents Management Report
    </h2>

    <p>
        Report generated:
        <?php echo date("d F Y, h:i A"); ?>
    </p>

    <p>
        Prepared by:
        <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
    </p>

    <h2>Residents Statistics</h2>

    <div class="report-summary">

        <div class="summary-card">
            <h3>Total Residents</h3>
            <p><?php echo (int)$stats['total_residents']; ?></p>
        </div>

        <div class="summary-card">
            <h3>Approved</h3>
            <p><?php echo (int)$stats['approved']; ?></p>
        </div>

        <div class="summary-card">
            <h3>Pending</h3>
            <p><?php echo (int)$stats['pending']; ?></p>
        </div>

        <div class="summary-card">
            <h3>Rejected</h3>
            <p><?php echo (int)$stats['rejected']; ?></p>
        </div>

        <div class="summary-card">
            <h3>Rooms Allocated</h3>
            <p><?php echo (int)$stats['allocated']; ?></p>
        </div>

    </div>

    <h2>Resident Information</h2>

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>
                    <th>Resident ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Telephone</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Room Number</th>
                    <th>Room Type</th>
                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0) { ?>

                    <?php while ($resident = mysqli_fetch_assoc($result)) { ?>

                        <tr>

                            <td><?php echo (int)$resident['id']; ?></td>

                            <td>
                                <?php echo htmlspecialchars($resident['fullname']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($resident['email']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($resident['phone'] ?? 'Not provided'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($resident['course'] ?? 'Not provided'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($resident['year_of_study'] ?? 'Not provided'); ?>
                            </td>

                            <td>

                                <?php
                                if ($resident['status'] === 'Approved') {
                                    echo '<span class="approved">Approved</span>';
                                } elseif ($resident['status'] === 'Pending') {
                                    echo '<span class="pending">Pending</span>';
                                } elseif ($resident['status'] === 'Rejected') {
                                    echo '<span class="rejected">Rejected</span>';
                                } else {
                                    echo 'Not Applied';
                                }
                                ?>

                            </td>

                            <td>
                                <?php echo htmlspecialchars($resident['room_number'] ?? 'Not Allocated'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($resident['room_type'] ?? 'N/A'); ?>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="9">No residents registered.</td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

    <br>

    <div class="no-print">

        <button onclick="window.print()" class="report-button">
            Print Residents Report
        </button>

        <br><br>

        <a href="admin_dashboard.php">
            Back to Admin Dashboard
        </a>

    </div>

</div>

</body>
</html>
