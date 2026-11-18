
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$messageType = "";

// Retrieve registered residents
$users_sql = "SELECT id, fullname, email FROM users ORDER BY fullname ASC";
$users_result = mysqli_query($conn, $users_sql);

if (!$users_result) {
    die("Unable to retrieve residents: " . mysqli_error($conn));
}

// Process notification form
if (isset($_POST['send_notification'])) {

    $recipient = trim($_POST['recipient'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $notificationMessage = trim($_POST['message'] ?? '');

    if ($recipient === "" || $title === "" || $notificationMessage === "") {

        $message = "Please fill in all fields.";
        $messageType = "error";

    } else {

        try {

            mysqli_begin_transaction($conn);

            if ($recipient === "all") {

                // Send notification to all registered residents
                $sql = "INSERT INTO notifications (user_id, title, message)
                        SELECT id, ?, ?
                        FROM users";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "ss",
                    $title,
                    $notificationMessage
                );

                mysqli_stmt_execute($stmt);

                $sentCount = mysqli_stmt_affected_rows($stmt);

                mysqli_stmt_close($stmt);

            } else {

                // Send notification to one selected resident
                $user_id = filter_var($recipient, FILTER_VALIDATE_INT);

                if (!$user_id || $user_id <= 0) {
                    throw new Exception("Invalid resident selected.");
                }

                $check_sql = "SELECT id FROM users WHERE id = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);

                mysqli_stmt_bind_param($check_stmt, "i", $user_id);
                mysqli_stmt_execute($check_stmt);

                $check_result = mysqli_stmt_get_result($check_stmt);
                $resident = mysqli_fetch_assoc($check_result);

                mysqli_stmt_close($check_stmt);

                if (!$resident) {
                    throw new Exception("Selected resident does not exist.");
                }

                $sql = "INSERT INTO notifications (user_id, title, message)
                        VALUES (?, ?, ?)";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "iss",
                    $user_id,
                    $title,
                    $notificationMessage
                );

                mysqli_stmt_execute($stmt);

                $sentCount = 1;

                mysqli_stmt_close($stmt);
            }

            mysqli_commit($conn);

            if ($sentCount > 0) {
                $message = "Notification successfully sent to "
                         . $sentCount . " resident(s).";
                $messageType = "success";
            } else {
                $message = "No residents were available to receive the notification.";
                $messageType = "error";
            }

        } catch (Throwable $e) {

            mysqli_rollback($conn);

            $message = "Unable to send notification: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Send Notification - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.notification-container {
    max-width: 750px;
    margin: 35px auto;
    padding: 25px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.notification-container h1 {
    color: #198754;
    text-align: center;
}

.notification-container label {
    display: block;
    margin-top: 18px;
    margin-bottom: 7px;
    font-weight: bold;
}

.notification-container input,
.notification-container select,
.notification-container textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

.notification-container textarea {
    min-height: 150px;
    resize: vertical;
}

.send-button {
    width: 100%;
    margin-top: 22px;
    padding: 13px;
    background: #198754;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
}

.send-button:hover {
    background: #146c43;
}

.message {
    padding: 12px;
    margin: 15px 0;
    border-radius: 5px;
}

.success {
    background: #d1e7dd;
    color: #0f5132;
}

.error {
    background: #f8d7da;
    color: #842029;
}

.back-link {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 15px;
    background: #198754;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

</style>

</head>

<body>

<div class="notification-container">

    <h1>Send Resident Notification</h1>

    <p>
        Use this page to send important announcements
        and updates to hostel residents.
    </p>

    <?php if ($message !== "") { ?>

        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>

    <form method="POST" action="">

        <label for="recipient">Select Notification Recipient</label>

        <select name="recipient" id="recipient" required>

            <option value="">-- Select Recipient --</option>

            <option value="all"
                <?php echo (($_POST['recipient'] ?? '') === 'all') ? 'selected' : ''; ?>>
                All Registered Residents
            </option>

            <?php
            mysqli_data_seek($users_result, 0);

            while ($resident = mysqli_fetch_assoc($users_result)) {
            ?>

                <option
                    value="<?php echo (int)$resident['id']; ?>"
                    <?php echo (($_POST['recipient'] ?? '') == $resident['id']) ? 'selected' : ''; ?>>

                    <?php
                    echo htmlspecialchars($resident['fullname'])
                       . " - "
                       . htmlspecialchars($resident['email']);
                    ?>

                </option>

            <?php } ?>

        </select>

        <label for="title">Notification Title</label>

        <input
            type="text"
            name="title"
            id="title"
            maxlength="200"
            placeholder="Enter notification title"
            value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
            required
        >

        <label for="message">Notification Message</label>

        <textarea
            name="message"
            id="message"
            placeholder="Write your notification here..."
            required
        ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>

        <button
            type="submit"
            name="send_notification"
            class="send-button">

            Send Notification

        </button>

    </form>

    <a href="admin_dashboard.php" class="back-link">
        Back to Admin Dashboard
    </a>

</div>

</body>
</html>
