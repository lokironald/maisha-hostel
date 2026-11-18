<?php
include "database.php";

$message = "";

if (isset($_POST['register'])) {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if (empty($fullname) || empty($email) ||
        empty($password) || empty($confirmPassword)) {

        $message = "All fields are required!";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Enter a valid email address!";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters!";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match!";

    } else {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $sql = "INSERT INTO administrators
                (fullname, email, password)
                VALUES (?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $fullname,
                $email,
                $hashed_password
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Administrator registered successfully!";

            } else {

                $message = "Registration failed. Email may already exist.";

            }

            mysqli_stmt_close($stmt);

        } else {

            $message = "Database error. Please try again.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Registration - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <h2>Maisha Hostel Administrator Registration</h2>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

    <form method="POST" id="registrationForm">

        <label>Full Name:</label><br>

        <input type="text"
               name="fullname"
               required><br><br>

        <label>Email Address:</label><br>

        <input type="email"
               name="email"
               required><br><br>

        <label>Password:</label><br>

        <input type="password"
               name="password"
               required><br><br>

        <label>Confirm Password:</label><br>

        <input type="password"
               id="confirmPassword"
               name="confirmPassword"
               required><br><br>

        <button type="submit" name="register">
            Register Administrator
        </button>

    </form>

    <p>
        Already registered?
        <a href="admin_login.php">Admin Login</a>
    </p>

    <script src="script.js"></script>

</body>

</html>