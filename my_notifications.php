
<?php
session_start();
include "database.php";

// Protect resident page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$message = "";
$messageType = "";

// Mark a notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {

    $notification_id = (int)($_POST['notification_id'] ?? 0);

    if ($notification_id > 0) {

        $update_sql = "UPDATE notifications
                       SET is_read = 1
                       WHERE id = ?
                       AND user_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        mysqli_stmt_bind_param(
            $update_stmt,
            "ii",
            $notification_id,
            $user_id
        );

        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Notification marked as read.";
            $messageType = "success";
        } else {
            $message = "Unable to update notification.";
            $messageType = "error";
        }

        mysqli_stmt_close($update_stmt);

    } else {
        $message = "Invalid notification.";
        $messageType = "error";
    }
}

// Retrieve resident information
$user_sql = "SELECT fullname FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);

mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);

$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    die("Resident account not found.");
}

// Count unread notifications
$count_sql = "SELECT COUNT(*) AS unread_count
              FROM notifications
              WHERE user_id = ?
              AND is_read = 0";

$count_stmt = mysqli_prepare($conn, $count_sql);

mysqli_stmt_bind_param($count_stmt, "i", $user_id);
mysqli_stmt_execute($count_stmt);

$count_result = mysqli_stmt_get_result($count_stmt);
$count_data = mysqli_fetch_assoc($count_result);

$unreadCount = (int)$count_data['unread_count'];

// Retrieve all notifications belonging to this resident
$sql = "SELECT id, title, message, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC, id DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Notifications - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.notification-container {
    max-width: 900px;
    margin: 30px auto;
    padding: 25px;
}

.notification-header {
    background: white;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.notification-header h1 {
    color: #198754;
}

.unread-count {
    color: #198754;
    font-weight: bold;
}

.notification-card {
    background: white;
    padding: 22px;
    margin-bottom: 18px;
    border-radius: 10px;
    border-left: 5px solid #198754;
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
}

.notification-card.unread {
    border-left-color: #dc3545;
    background: #fffafa;
}

.notification-card h3 {
    margin-top: 0;
    color: #198754;
}

.notification-message {
    line-height: 1.7;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.notification-date {
    color: #777;
    font-size: 13px;
}

.notification-status {
    font-weight: bold;
    margin: 12px 0;
}

.unread-status {
    color: #dc3545;
}

.read-status {
    color: #198754;
}

.read-button {
    padding: 9px 15px;
    background: #198754;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.read-button:hover {
    background: #146c43;
}

.message {
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 18px;
}

.success {
    background: #d1e7dd;
    color: #0f5132;
}

.error {
    background: #f8d7da;
    color: #842029;
}

.no-notifications {
    background: white;
    padding: 30px;
    text-align: center;
    border-radius: 10px;
}

.back-button {
    display: inline-block;
    padding: 11px 18px;
    background: #198754;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 15px;
}

@media print {
    .back-button,
    .read-button,
    .message {
        display: none;
    }
}

</style>

</head>

<body>

<div class="notification-container">

    <div class="notification-header">

        <h1>My Notifications</h1>

        <p>
            Welcome,
            <strong>
                <?php echo htmlspecialchars($user['fullname']); ?>
            </strong>
        </p>

        <p>
            Unread Notifications:
            <span class="unread-count">
                <?php echo $unreadCount; ?>
            </span>
        </p>

    </div>

    <?php if ($message !== "") { ?>

        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>

    <h2>All My Notifications</h2>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <?php while ($notification = mysqli_fetch_assoc($result)) { ?>

            <div class="notification-card
                <?php echo ((int)$notification['is_read'] === 0) ? 'unread' : ''; ?>">

                <h3>
                    <?php echo htmlspecialchars($notification['title']); ?>
                </h3>

                <p class="notification-message">
                    <?php echo htmlspecialchars($notification['message']); ?>
                </p>

                <p class="notification-date">
                    Received:
                    <?php echo htmlspecialchars($notification['created_at']); ?>
                </p>

                <?php if ((int)$notification['is_read'] === 0) { ?>

                    <p class="notification-status unread-status">
                        Unread
                    </p>

                    <form method="POST" action="my_notifications.php">

                        <input
                            type="hidden"
                            name="notification_id"
                            value="<?php echo (int)$notification['id']; ?>"
                        >

                        <button
                            type="submit"
                            name="mark_read"
                            class="read-button">

                            Mark as Read

                        </button>

                    </form>

                <?php } else { ?>

                    <p class="notification-status read-status">
                        Read
                    </p>

                <?php } ?>

            </div>

        <?php } ?>

    <?php } else { ?>

        <div class="no-notifications">

            <h3>No Notifications Yet</h3>

            <p>
                You currently have no notifications from the hostel administrator.
            </p>

        </div>

    <?php } ?>

    <a href="dashboard.php" class="back-button">
        Back to Resident Dashboard
    </a>

</div>

</body>
</html>
