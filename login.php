<head>
    <title>Maisha Hostel Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<?php
session_start();

include "database.php";

$message = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];

            header("Location: dashboard.php");
            exit();

        } else {
            $message = "Incorrect password!";
        }

    } else {
        $message = "Account not found!";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Maisha Hostel Login</title>
</head>

<body>

<h2>Maisha Hostel Resident Login</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<form method="POST">

    <label>Email Address:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit" name="login">
        Login
    </button>

</form>

<p>Don't have an account?
<a href="register.php">Register here</a>
</p>

</body>
</html>