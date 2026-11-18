
<?php
session_start();
include "database.php";

// Protect administrator dashboard
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Total registered residents
$users_result = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM users");
$users = mysqli_fetch_assoc($users_result);

// Total applications
$applications_result = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM applications");
$applications = mysqli_fetch_assoc($applications_result);

// Pending applications
$pending_result = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM applications WHERE status='Pending'");
$pending = mysqli_fetch_assoc($pending_result);

// Approved applications
$approved_result = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM applications WHERE status='Approved'");
$approved = mysqli_fetch_assoc($approved_result);

// Rejected applications
$rejected_result = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM applications WHERE status='Rejected'");
$rejected = mysqli_fetch_assoc($rejected_result);

// Calculate approval percentage
$totalApplications = (int)$applications['total'];
$approvedApplications = (int)$approved['total'];

$approvalPercentage = $totalApplications > 0
    ? round(($approvedApplications / $totalApplications) * 100, 1)
    : 0;

// Recent applications
$recent_sql = "SELECT
                    applications.id,
                    applications.phone,
                    applications.course,
                    applications.status,
                    users.fullname,
                    users.email
               FROM applications
               INNER JOIN users
               ON applications.user_id = users.id
               ORDER BY applications.id DESC
               LIMIT 5";

$recent_result = mysqli_query($conn, $recent_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.dashboard-welcome {
    background: #198754;
    color: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.dashboard-welcome h1 {
    margin-top: 0;
}

.admin-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin: 25px 0;
}

.admin-card {
    background: white;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    border-left: 5px solid #198754;
}

.admin-card h3 {
    font-size: 15px;
    color: #555;
}

.admin-card p {
    font-size: 30px;
    font-weight: bold;
    color: #198754;
    margin-bottom: 0;
}

.recent-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-top: 30px;
    overflow-x: auto;
}

.recent-section table {
    width: 100%;
    border-collapse: collapse;
}

.recent-section th,
.recent-section td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.recent-section th {
    background: #198754;
    color: white;
}

.status-approved {
    color: green;
    font-weight: bold;
}

.status-pending {
    color: orange;
    font-weight: bold;
}

.status-rejected {
    color: red;
    font-weight: bold;
}

.approval-summary {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}

.progress-track {
    background: #e5e5e5;
    height: 15px;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    background: #198754;
    height: 100%;
    width: <?php echo $approvalPercentage; ?>%;
}

@media print {
    .admin-sidebar,
    .admin-shortcuts {
        display: none;
    }

    .admin-main {
        width: 100%;
    }
}

</style>

</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->

    <aside class="admin-sidebar">

        <h2>MAISHA HOSTEL</h2>

        <p>Administration</p>

        <a href="admin_dashboard.php">Dashboard</a>

        <a href="manage_applications.php">Manage Applications</a>

        <a href="view_residents.php">Registered Residents</a>

        <a href="resident_report.php">Resident Reports</a>

        <a href="manage_rooms.php">Manage Hostel Rooms</a>

        <a href="room_occupancy.php">Room Occupancy</a>

        <!-- FEE MANAGEMENT LINKS -->

        <a href="record_payment.php">Record Payment</a>

        <a href="payment_history.php">Payment History</a>

        <a href="resident_balances.php">Fee Balances</a>

        <a href="#" onclick="confirmLogout(event)">Logout</a>

    </aside>


    <!-- MAIN DASHBOARD -->

    <main class="admin-main">

        <div class="dashboard-welcome">

            <h1>Administrator Dashboard</h1>

            <h3>
                Welcome back,
                <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!
            </h3>

            <p>Welcome to Maisha Hostel Management System.</p>

        </div>


        <!-- SYSTEM STATISTICS -->

        <h2>System Overview</h2>

        <section class="admin-cards">

            <div class="admin-card">
                <h3>Total Residents</h3>
                <p><?php echo (int)$users['total']; ?></p>
            </div>

            <div class="admin-card">
                <h3>Total Applications</h3>
                <p><?php echo $totalApplications; ?></p>
            </div>

            <div class="admin-card">
                <h3>Pending Applications</h3>
                <p><?php echo (int)$pending['total']; ?></p>
            </div>

            <div class="admin-card">
                <h3>Approved Applications</h3>
                <p><?php echo $approvedApplications; ?></p>
            </div>

            <div class="admin-card">
                <h3>Rejected Applications</h3>
                <p><?php echo (int)$rejected['total']; ?></p>
            </div>

        </section>


        <!-- APPROVAL SUMMARY -->

        <div class="approval-summary">

            <h2>Application Approval Summary</h2>

            <p>
                Approved Applications:
                <strong><?php echo $approvalPercentage; ?>%</strong>
            </p>

            <div class="progress-track">
                <div class="progress-fill"></div>
            </div>

        </div>


        <!-- RECENT APPLICATIONS -->

        <div class="recent-section">

            <h2>Recent Applications</h2>

            <table>

                <tr>
                    <th>Applicant</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Status</th>
                </tr>

                <?php if ($recent_result && mysqli_num_rows($recent_result) > 0) { ?>

                    <?php while ($row = mysqli_fetch_assoc($recent_result)) { ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($row['fullname']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['email']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['course'] ?? 'Not provided'); ?>
                            </td>

                            <td>

                                <?php
                                $status = $row['status'] ?? 'Pending';
                                $statusClass = strtolower($status);
                                ?>

                                <span class="status-<?php echo htmlspecialchars($statusClass); ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="4">No applications available.</td>
                    </tr>

                <?php } ?>

            </table>

        </div>


        <!-- QUICK MANAGEMENT -->

        <h2>Quick Management</h2>

        <section class="admin-shortcuts">

            <a href="manage_applications.php">
                Manage Applications
            </a>

            <a href="view_residents.php">
                View Residents
            </a>

            <a href="resident_report.php">
                Resident Reports
            </a>

            <a href="manage_rooms.php">
                Manage Rooms
            </a>

            <a href="room_occupancy.php">
                View Room Occupancy
            </a>

            <!-- FEE MANAGEMENT -->

            <a href="record_payment.php">
                Record Hostel Payment
            </a>

            <a href="payment_history.php">
                Payment History
            </a>

            <a href="resident_balances.php">
                Resident Fee Balances
            </a>

            <a href="#" onclick="confirmLogout(event)">
                Logout
            </a>

        </section>

    </main>

</div>


<!-- JAVASCRIPT LOGOUT CONFIRMATION -->

<script>

function confirmLogout(event) {

    event.preventDefault();

    let answer = confirm("Are you sure you want to logout?");

    if (answer) {
        window.location.href = "admin_logout.php";
    }

}

</script>

</body>
</html>
