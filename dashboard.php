
<?php
session_start();
include "database.php";

// Identify logged-in account
$isAdmin = isset($_SESSION['admin_id']);
$isResident = isset($_SESSION['user_id']);

// Prevent simultaneous sessions
if ($isAdmin && $isResident) {
    die("Both sessions are active. Please logout and login again.");
}

// Protect dashboard
if (!$isAdmin && !$isResident) {
    header("Location: index.php");
    exit();
}

// Safe display helper
function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// =====================================
// ADMIN DASHBOARD DATA
// =====================================

if ($isAdmin) {

    // Total residents
    $users_result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM users"
    );

    $users = $users_result
        ? mysqli_fetch_assoc($users_result)
        : ['total' => 0];

    // Total applications
    $applications_result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM applications"
    );

    $applications = $applications_result
        ? mysqli_fetch_assoc($applications_result)
        : ['total' => 0];

    // Pending applications
    $pending_result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM applications
         WHERE status = 'Pending'"
    );

    $pending = $pending_result
        ? mysqli_fetch_assoc($pending_result)
        : ['total' => 0];

    // Approved applications
    $approved_result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM applications
         WHERE status = 'Approved'"
    );

    $approved = $approved_result
        ? mysqli_fetch_assoc($approved_result)
        : ['total' => 0];

    // Rejected applications
    $rejected_result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM applications
         WHERE status = 'Rejected'"
    );

    $rejected = $rejected_result
        ? mysqli_fetch_assoc($rejected_result)
        : ['total' => 0];

    $totalResidents = (int)$users['total'];
    $totalApplications = (int)$applications['total'];
    $pendingApplications = (int)$pending['total'];
    $approvedApplications = (int)$approved['total'];
    $rejectedApplications = (int)$rejected['total'];

    $approvalPercentage = $totalApplications > 0
        ? round(
            ($approvedApplications / $totalApplications) * 100,
            1
        )
        : 0;

    // Recent applications
    $recent_sql = "SELECT
                        applications.id,
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
}

// =====================================
// RESIDENT DASHBOARD DATA
// =====================================

if ($isResident) {

    $user_id = (int)$_SESSION['user_id'];

    $notification_sql = "SELECT COUNT(*) AS unread_count
                         FROM notifications
                         WHERE user_id = ?
                         AND is_read = 0";

    $notification_stmt = mysqli_prepare(
        $conn,
        $notification_sql
    );

    $unreadCount = 0;

    if ($notification_stmt) {

        mysqli_stmt_bind_param(
            $notification_stmt,
            "i",
            $user_id
        );

        mysqli_stmt_execute($notification_stmt);

        $notification_result = mysqli_stmt_get_result(
            $notification_stmt
        );

        if ($notification_result) {

            $notification_data = mysqli_fetch_assoc(
                $notification_result
            );

            $unreadCount = (int)$notification_data['unread_count'];
        }

        mysqli_stmt_close($notification_stmt);
    }
}

// Account name
$displayName = $isAdmin
    ? ($_SESSION['admin_name'] ?? 'Administrator')
    : ($_SESSION['fullname'] ?? 'Resident');

$displayName = clean($displayName);

$initial = strtoupper(substr($displayName, 0, 1));

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Maisha Hostel | Dashboard</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f7f4;
            color: #333;
        }

        a {
            text-decoration: none;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 255px;
            height: 100vh;
            background: #145c38;
            color: white;
            padding: 22px 15px;
            overflow-y: auto;
            z-index: 1000;
            transition: 0.3s;
        }

        .brand {
            text-align: center;
            padding: 12px 5px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .brand h2 {
            font-size: 22px;
            letter-spacing: 1px;
        }

        .brand p {
            color: #d4e9dc;
            font-size: 12px;
            margin-top: 8px;
        }

        .menu-heading {
            font-size: 11px;
            color: #b9d6c5;
            margin: 25px 12px 10px;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 12px;
            color: white;
            border-radius: 7px;
            margin-bottom: 5px;
            font-size: 14px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #198754;
        }

        .menu-icon {
            width: 24px;
            text-align: center;
            font-size: 18px;
        }

        .logout-link {
            margin-top: 25px;
            background: #b02a37;
        }

        .logout-link:hover {
            background: #dc3545 !important;
        }

        .sidebar-badge {
            background: #dc3545;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: auto;
        }

        /* MAIN CONTENT */

        .main-content {
            margin-left: 255px;
            padding: 25px;
            min-height: 100vh;
        }

        /* TOP BAR */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 16px 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar h2 {
            font-size: 20px;
            color: #145c38;
        }

        .menu-toggle {
            display: none;
            background: #198754;
            color: white;
            border: none;
            padding: 9px 13px;
            border-radius: 5px;
            font-size: 20px;
            cursor: pointer;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-avatar {
            width: 43px;
            height: 43px;
            border-radius: 50%;
            background: #198754;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .profile-info strong {
            display: block;
            font-size: 14px;
        }

        .profile-info small {
            color: #777;
            font-size: 12px;
        }

        /* WELCOME CARD */

        .welcome-card {
            background: linear-gradient(120deg, #145c38, #198754);
            color: white;
            padding: 32px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .welcome-card h1 {
            font-size: 26px;
            margin-bottom: 12px;
        }

        .welcome-card p {
            color: #e0f0e6;
            line-height: 1.7;
        }

        .section-title {
            font-size: 20px;
            margin: 25px 0 15px;
            color: #145c38;
        }

        /* ADMIN STATISTICS */

        .admin-cards {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(175px, 1fr)
            );
            gap: 18px;
            margin-bottom: 25px;
        }

        .admin-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #198754;
        }

        .admin-card h3 {
            color: #777;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .admin-card p {
            color: #198754;
            font-size: 30px;
            font-weight: bold;
        }

        /* APPROVAL PROGRESS */

        .approval-summary {
            background: white;
            padding: 23px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .approval-summary h2 {
            color: #145c38;
            margin-bottom: 12px;
            font-size: 19px;
        }

        .progress-track {
            background: #e5e5e5;
            height: 15px;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 15px;
        }

        .progress-fill {
            background: #198754;
            height: 100%;
            border-radius: 20px;
        }

        /* RECENT APPLICATIONS */

        .recent-section {
            background: white;
            padding: 23px;
            border-radius: 10px;
            overflow-x: auto;
            margin-bottom: 25px;
        }

        .recent-section h2 {
            color: #145c38;
            margin-bottom: 18px;
            font-size: 19px;
        }

        .recent-section table {
            width: 100%;
            border-collapse: collapse;
            min-width: 550px;
        }

        .recent-section th,
        .recent-section td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eee;
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
            color: #d17a00;
            font-weight: bold;
        }

        .status-rejected {
            color: red;
            font-weight: bold;
        }

        /* ADMIN QUICK LINKS */

        .admin-shortcuts {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(180px, 1fr)
            );
            gap: 15px;
        }

        .admin-shortcuts a {
            background: white;
            color: #198754;
            padding: 19px;
            border-radius: 9px;
            border: 1px solid #dce9df;
            font-weight: bold;
            transition: 0.3s;
        }

        .admin-shortcuts a:hover {
            background: #198754;
            color: white;
        }

        /* RESIDENT SERVICE CARDS */

        .resident-actions {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(220px, 1fr)
            );
            gap: 20px;
        }

        .resident-action-card {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            border-bottom: 4px solid #198754;
            transition: 0.3s;
        }

        .resident-action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        .service-icon {
            font-size: 32px;
            margin-bottom: 14px;
        }

        .resident-action-card h3 {
            color: #145c38;
            margin-bottom: 12px;
        }

        .resident-action-card p {
            color: #777;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .resident-action-card a {
            display: inline-block;
            background: #198754;
            color: white;
            padding: 11px 16px;
            border-radius: 6px;
            font-size: 13px;
        }

        .resident-action-card a:hover {
            background: #145c38;
        }

        /* NOTIFICATIONS */

        .notification-badge {
            background: #dc3545;
            color: white;
            border-radius: 20px;
            padding: 3px 8px;
            font-size: 11px;
        }

        .notification-alert {
            background: #fff3cd;
            color: #664d03;
            padding: 15px;
            border-radius: 7px;
            margin-top: 18px;
        }

        .notification-alert a {
            color: #145c38;
            font-weight: bold;
            margin-left: 5px;
        }

        /* FOOTER */

        .footer {
            text-align: center;
            padding: 25px;
            color: #777;
            font-size: 13px;
            margin-top: 30px;
        }

        /* MOBILE OVERLAY */

        .sidebar-overlay {
            display: none;
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 768px) {

            .sidebar {
                left: -270px;
            }

            .sidebar.open {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .menu-toggle {
                display: block;
            }

            .topbar {
                padding: 12px;
            }

            .topbar h2 {
                font-size: 17px;
            }

            .profile-info {
                display: none;
            }

            .welcome-card {
                padding: 23px;
            }

            .welcome-card h1 {
                font-size: 22px;
            }

            .admin-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .admin-card {
                padding: 15px;
            }

            .admin-card p {
                font-size: 24px;
            }

            .resident-actions {
                grid-template-columns: 1fr;
            }

            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.45);
                z-index: 900;
            }
        }

        @media print {

            .sidebar,
            .topbar,
            .admin-shortcuts,
            .resident-actions {
                display: none !important;
            }

            .main-content {
                margin: 0;
            }
        }

    </style>

</head>

<body>

<div class="dashboard-layout">

    <!-- SIDEBAR -->

    <aside class="sidebar" id="sidebar">

        <div class="brand">

            <h2>MAISHA HOSTEL</h2>

            <p>Residents Management System</p>

        </div>

        <?php if ($isAdmin) { ?>

            <p class="menu-heading">ADMINISTRATION</p>

            <a href="dashboard.php" class="active">
                <span class="menu-icon">⌂</span>
                Dashboard
            </a>

            <a href="manage_applications.php">
                <span class="menu-icon">📋</span>
                Manage Applications
            </a>

            <a href="view_residents.php">
                <span class="menu-icon">👥</span>
                Registered Residents
            </a>

            <a href="resident_report.php">
                <span class="menu-icon">📊</span>
                Resident Reports
            </a>

            <p class="menu-heading">ROOM MANAGEMENT</p>

            <a href="manage_rooms.php">
                <span class="menu-icon">🏠</span>
                Manage Rooms
            </a>

            <a href="room_occupancy.php">
                <span class="menu-icon">🛏️</span>
                Room Occupancy
            </a>

            <p class="menu-heading">FEE MANAGEMENT</p>

            <a href="record_payment.php">
                <span class="menu-icon">💳</span>
                Record Payment
            </a>

            <a href="payment_history.php">
                <span class="menu-icon">📜</span>
                Payment History
            </a>

            <a href="resident_balances.php">
                <span class="menu-icon">💰</span>
                Fee Balances
            </a>

            <a href="admin_logout.php"
               class="logout-link"
               onclick="return confirm('Are you sure you want to logout?')">

                <span class="menu-icon">🚪</span>
                Logout

            </a>

        <?php } else { ?>

            <p class="menu-heading">RESIDENT MENU</p>

            <a href="dashboard.php" class="active">
                <span class="menu-icon">⌂</span>
                My Dashboard
            </a>

            <a href="application.php">
                <span class="menu-icon">📝</span>
                Apply for Accommodation
            </a>

            <a href="view_application.php">
                <span class="menu-icon">📋</span>
                My Application
            </a>

            <a href="my_room.php">
                <span class="menu-icon">🏠</span>
                My Room
            </a>

            <a href="my_payments.php">
                <span class="menu-icon">💳</span>
                My Payments
            </a>

            <a href="my_notifications.php">
                <span class="menu-icon">🔔</span>
                Notifications

                <?php if ($unreadCount > 0) { ?>

                    <span class="sidebar-badge">
                        <?php echo $unreadCount; ?>
                    </span>

                <?php } ?>

            </a>

            <a href="logout.php"
               class="logout-link"
               onclick="return confirm('Are you sure you want to logout?')">

                <span class="menu-icon">🚪</span>
                Logout

            </a>

        <?php } ?>

    </aside>

    <div class="sidebar-overlay" id="overlay"></div>

    <!-- MAIN CONTENT -->

    <main class="main-content">

        <!-- TOP BAR -->

        <header class="topbar">

            <div class="topbar-left">

                <button class="menu-toggle"
                        id="menuToggle"
                        type="button"
                        aria-label="Open navigation">

                    ☰

                </button>

                <h2>
                    <?php
                    echo $isAdmin
                        ? "Admin Dashboard"
                        : "Resident Dashboard";
                    ?>
                </h2>

            </div>

            <div class="profile">

                <div class="profile-avatar">
                    <?php echo clean($initial); ?>
                </div>

                <div class="profile-info">

                    <strong><?php echo $displayName; ?></strong>

                    <small>
                        <?php
                        echo $isAdmin
                            ? "Administrator"
                            : "Resident";
                        ?>
                    </small>

                </div>

            </div>

        </header>

        <?php if ($isAdmin) { ?>

            <!-- ADMIN DASHBOARD -->

            <section class="welcome-card">

                <h1>
                    Welcome back, <?php echo $displayName; ?>!
                </h1>

                <p>
                    Welcome to Maisha Hostel Management System.
                    Manage residents, accommodation applications,
                    rooms and hostel payments from one place.
                </p>

            </section>

            <h2 class="section-title">
                System Overview
            </h2>

            <section class="admin-cards">

                <div class="admin-card">

                    <h3>👥 Total Residents</h3>

                    <p><?php echo $totalResidents; ?></p>

                </div>

                <div class="admin-card">

                    <h3>📋 Total Applications</h3>

                    <p><?php echo $totalApplications; ?></p>

                </div>

                <div class="admin-card">

                    <h3>⏳ Pending Applications</h3>

                    <p><?php echo $pendingApplications; ?></p>

                </div>

                <div class="admin-card">

                    <h3>✅ Approved Applications</h3>

                    <p><?php echo $approvedApplications; ?></p>

                </div>

                <div class="admin-card">

                    <h3>❌ Rejected Applications</h3>

                    <p><?php echo $rejectedApplications; ?></p>

                </div>

            </section>

            <!-- APPROVAL SUMMARY -->

            <div class="approval-summary">

                <h2>Application Approval Summary</h2>

                <p>
                    Approved Applications:
                    <strong>
                        <?php echo $approvalPercentage; ?>%
                    </strong>
                </p>

                <div class="progress-track">

                    <div class="progress-fill"
                         style="width: <?php echo $approvalPercentage; ?>%;">
                    </div>

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

                    <?php
                    if (
                        $recent_result &&
                        mysqli_num_rows($recent_result) > 0
                    ) {
                    ?>

                        <?php while ($row = mysqli_fetch_assoc($recent_result)) { ?>

                            <?php
                            $status = $row['status'] ?? 'Pending';

                            $statusClass = strtolower(
                                preg_replace(
                                    '/[^a-zA-Z0-9_-]/',
                                    '',
                                    $status
                                )
                            );
                            ?>

                            <tr>

                                <td>
                                    <?php echo clean($row['fullname']); ?>
                                </td>

                                <td>
                                    <?php echo clean($row['email']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo clean(
                                        $row['course'] ?? 'Not provided'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <span class="status-<?php echo clean($statusClass); ?>">
                                        <?php echo clean($status); ?>
                                    </span>
                                </td>

                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>
                            <td colspan="4">
                                No applications available.
                            </td>
                        </tr>

                    <?php } ?>

                </table>

            </div>

            <!-- QUICK MANAGEMENT -->

            <h2 class="section-title">
                Quick Management
            </h2>

            <section class="admin-shortcuts">

                <a href="manage_applications.php">
                    📋 Manage Applications
                </a>

                <a href="view_residents.php">
                    👥 View Residents
                </a>

                <a href="resident_report.php">
                    📊 Resident Reports
                </a>

                <a href="manage_rooms.php">
                    🏠 Manage Rooms
                </a>

                <a href="room_occupancy.php">
                    🛏️ Room Occupancy
                </a>

                <a href="record_payment.php">
                    💳 Record Payment
                </a>

                <a href="payment_history.php">
                    📜 Payment History
                </a>

                <a href="resident_balances.php">
                    💰 Fee Balances
                </a>

            </section>

        <?php } else { ?>

            <!-- RESIDENT DASHBOARD -->

            <section class="welcome-card">

                <h1>
                    Welcome, <?php echo $displayName; ?>!
                </h1>

                <p>
                    Welcome to your Maisha Hostel resident dashboard.
                    Manage your accommodation, room information,
                    payments and notifications from here.
                </p>

                <?php if ($unreadCount > 0) { ?>

                    <div class="notification-alert">

                        🔔 You have
                        <strong>
                            <?php echo $unreadCount; ?>
                        </strong>
                        unread notification(s).

                        <a href="my_notifications.php">
                            Read your messages
                        </a>

                    </div>

                <?php } ?>

            </section>

            <h2 class="section-title">
                My Resident Services
            </h2>

            <section class="resident-actions">

                <div class="resident-action-card">

                    <div class="service-icon">📝</div>

                    <h3>Apply for Accommodation</h3>

                    <p>
                        Submit your hostel accommodation application.
                    </p>

                    <a href="application.php">
                        Apply Now
                    </a>

                </div>

                <div class="resident-action-card">

                    <div class="service-icon">📋</div>

                    <h3>My Application</h3>

                    <p>
                        View your submitted accommodation details
                        and application status.
                    </p>

                    <a href="view_application.php">
                        View Application
                    </a>

                </div>

                <div class="resident-action-card">

                    <div class="service-icon">🏠</div>

                    <h3>My Room</h3>

                    <p>
                        View your allocated room number, room type,
                        room capacity and roommates.
                    </p>

                    <a href="my_room.php">
                        View My Room
                    </a>

                </div>

                <div class="resident-action-card">

                    <div class="service-icon">💳</div>

                    <h3>My Payments</h3>

                    <p>
                        View hostel fees, payments made,
                        outstanding balances and receipts.
                    </p>

                    <a href="my_payments.php">
                        View My Payments
                    </a>

                </div>

                <div class="resident-action-card">

                    <div class="service-icon">🔔</div>

                    <h3>
                        My Notifications

                        <?php if ($unreadCount > 0) { ?>

                            <span class="notification-badge">
                                <?php echo $unreadCount; ?> New
                            </span>

                        <?php } ?>

                    </h3>

                    <p>
                        Read hostel announcements, payment reminders
                        and room allocation updates.
                    </p>

                    <a href="my_notifications.php">
                        View Notifications
                    </a>

                </div>

            </section>

        <?php } ?>

        <!-- FOOTER -->

        <footer class="footer">

            <p>
                &copy; <?php echo date("Y"); ?>
                Maisha Hostel Management System.
                All Rights Reserved.
            </p>

        </footer>

    </main>

</div>

<!-- MOBILE SIDEBAR JAVASCRIPT -->

<script>

const menuToggle = document.getElementById("menuToggle");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

function openMenu() {

    sidebar.classList.add("open");
    overlay.classList.add("show");

}

function closeMenu() {

    sidebar.classList.remove("open");
    overlay.classList.remove("show");

}

menuToggle.addEventListener("click", function() {

    if (sidebar.classList.contains("open")) {

        closeMenu();

    } else {

        openMenu();

    }

});

overlay.addEventListener("click", closeMenu);

</script>

</body>
</html>
