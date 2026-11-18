<head>
    <title>My Hostel Application</title>
    <link rel="stylesheet" href="style.css">
</head>

<?php
session_start();
include "database.php";

// Check whether the resident is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve the resident's application
$sql = "SELECT * FROM applications WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$application = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Application - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h2>My Accommodation Application</h2>

    <?php if ($application) { ?>

        <!-- APPLICATION STATUS -->
        <div id="applicationStatus"
     data-status="<?php echo htmlspecialchars($application['status']); ?>">
</div>

<p id="statusMessage" class="status-notification"></p>

        <!-- APPLICATION DETAILS -->
        <h3>Application Details</h3>

        <p>
            <strong>Gender:</strong>
            <?php echo htmlspecialchars($application['gender']); ?>
        </p>

        <p>
            <strong>Phone Number:</strong>
            <?php echo htmlspecialchars($application['phone']); ?>
        </p>

        <p>
            <strong>Course:</strong>
            <?php echo htmlspecialchars($application['course']); ?>
        </p>

        <p>
            <strong>Year of Study:</strong>
            <?php echo htmlspecialchars($application['year_of_study']); ?>
        </p>

        <p>
            <strong>Room Type:</strong>
            <?php echo htmlspecialchars($application['room_type']); ?>
        </p>

        <p>
            <strong>Address:</strong>
            <?php echo htmlspecialchars($application['address']); ?>
        </p>

        <p>
            <strong>Application Status:</strong>
            <?php echo htmlspecialchars($application['status']); ?>
        </p>

    <?php } else { ?>

        <p>You have not submitted an accommodation application yet.</p>

    <?php } ?>

    <br>

    <a href="dashboard.php">Back to Dashboard</a>

    <br><br>

    <a href="logout.php">Logout</a>

    <script src="script.js"></script>

</body>
</html>
