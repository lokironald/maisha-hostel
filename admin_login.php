
<?php
session_start();
include "database.php";

$message = "";

// If administrator is already logged in, open dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password!";

    } else {

        $sql = "SELECT * FROM administrators WHERE email = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($admin = mysqli_fetch_assoc($result)) {

                if (password_verify($password, $admin['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['fullname'];

                    header("Location: dashboard.php");
                    exit();

                } else {

                    $message = "Incorrect email or password!";

                }

            } else {

                $message = "Incorrect email or password!";

            }

            mysqli_stmt_close($stmt);

        } else {

            $message = "Login could not be processed. Please try again.";

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

    <title>Administrator Login - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f7f5;
            margin: 0;
            padding: 0;
        }

        .login-container {
            width: 90%;
            max-width: 420px;
            margin: 70px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        h2 {
            text-align: center;
            color: #176b3a;
            margin-bottom: 25px;
        }

        label {
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #176b3a;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #10512b;
        }

        .message {
            text-align: center;
            color: #c0392b;
            margin-bottom: 15px;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #176b3a;
            text-decoration: none;
            font-weight: bold;
        }

    </style>

</head>

<body>

    <div class="login-container">

        <h2>Maisha Hostel Administrator Login</h2>

        <?php if (!empty($message)) { ?>

            <p class="message">
                <?php echo htmlspecialchars($message); ?>
            </p>

        <?php } ?>

        <form method="POST" action="">

            <label>Email Address:</label>

            <input
                type="email"
                name="email"
                placeholder="Enter administrator email"
                required
            >

            <br><br>

            <label>Password:</label>

            <input
                type="password"
                name="password"
                placeholder="Enter your password"
                required
            >

            <br><br>

            <button type="submit" name="login">
                Admin Login
            </button>

        </form>

        <div class="links">

            <p>
                <a href="login.php">
                    Resident Login
                </a>
            </p>

            <p>
                <a href="index.php">
                    Back to Home
                </a>
            </p>

        </div>

    </div>

</body>

</html>
