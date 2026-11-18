
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Maisha Hostel | Welcome</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f8f5;
            color: #333;
        }

        /* Navigation */

        nav {
            background: #145c35;
            color: white;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }

        .nav-links a:hover {
            color: #b9e7c9;
        }

        /* Hero section */

        .hero {
            background: linear-gradient(
                120deg,
                #145c35,
                #21864d
            );
            color: white;
            text-align: center;
            padding: 85px 20px;
        }

        .hero h1 {
            font-size: 42px;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 18px;
            line-height: 1.7;
            max-width: 700px;
            margin: auto;
        }

        .hero-button {
            display: inline-block;
            margin-top: 28px;
            padding: 14px 28px;
            background: white;
            color: #145c35;
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
        }

        .hero-button:hover {
            background: #e2f3e8;
        }

        /* Login section */

        .login-section {
            padding: 65px 20px;
            text-align: center;
        }

        .login-section h2 {
            color: #145c35;
            font-size: 30px;
            margin-bottom: 12px;
        }

        .section-description {
            color: #666;
            margin-bottom: 40px;
        }

        .login-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
        }

        .login-card {
            background: white;
            width: 340px;
            padding: 35px 28px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
        }

        .login-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.13);
        }

        .icon {
            font-size: 45px;
            margin-bottom: 18px;
        }

        .login-card h3 {
            color: #145c35;
            font-size: 23px;
            margin-bottom: 15px;
        }

        .login-card p {
            color: #666;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .login-button {
            display: block;
            background: #176b3a;
            color: white;
            text-decoration: none;
            padding: 14px;
            border-radius: 6px;
            font-weight: bold;
        }

        .login-button:hover {
            background: #104c29;
        }

        /* Features */

        .features {
            background: white;
            padding: 55px 20px;
            text-align: center;
        }

        .features h2 {
            color: #145c35;
            margin-bottom: 30px;
        }

        .feature-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 35px;
        }

        .feature {
            max-width: 220px;
            padding: 15px;
        }

        .feature h3 {
            color: #176b3a;
            margin-bottom: 10px;
        }

        .feature p {
            color: #666;
            line-height: 1.6;
        }

        /* Footer */

        footer {
            background: #103f26;
            color: white;
            text-align: center;
            padding: 25px 15px;
            line-height: 1.8;
        }

        @media (max-width: 600px) {

            nav {
                justify-content: center;
                text-align: center;
            }

            .nav-links a {
                margin: 0 8px;
            }

            .hero {
                padding: 60px 20px;
            }

            .hero h1 {
                font-size: 30px;
            }

            .hero p {
                font-size: 16px;
            }

            .login-card {
                width: 100%;
                max-width: 350px;
            }

        }

    </style>

</head>

<body>

    <!-- Navigation -->

    <nav>

        <div class="logo">
            MAISHA HOSTEL
        </div>

        <div class="nav-links">

            <a href="index.php">Home</a>

            <a href="#login">Login</a>

        </div>

    </nav>

    <!-- Welcome Section -->

    <section class="hero">

        <h1>Welcome to Maisha Hostel</h1>

        <p>
            Your comfort, security and convenience are our priority.
            Access hostel accommodation services through our
            Residents Information Management System.
        </p>

        <a href="#login" class="hero-button">
            Get Started
        </a>

    </section>

    <!-- Login Options -->

    <section class="login-section" id="login">

        <h2>Access Your Account</h2>

        <p class="section-description">
            Select your account type to continue.
        </p>

        <div class="login-container">

            <!-- Resident Login -->

            <div class="login-card">

                <div class="icon">🏠</div>

                <h3>Resident Portal</h3>

                <p>
                    Apply for accommodation, check your application
                    status, view room allocation, monitor payments
                    and receive hostel notifications.
                </p>

                <a href="login.php" class="login-button">
                    RESIDENT LOGIN
                </a>

            </div>

            <!-- Administrator Login -->

            <div class="login-card">

                <div class="icon">🛡️</div>

                <h3>Administrator Portal</h3>

                <p>
                    Manage residents, process accommodation
                    applications, allocate rooms, record payments
                    and monitor hostel operations.
                </p>

                <a href="admin_login.php" class="login-button">
                    ADMINISTRATOR LOGIN
                </a>

            </div>

        </div>

    </section>

    <!-- Features -->

    <section class="features">

        <h2>Our System Features</h2>

        <div class="feature-container">

            <div class="feature">

                <h3>Accommodation</h3>

                <p>
                    Submit and manage hostel accommodation applications.
                </p>

            </div>

            <div class="feature">

                <h3>Room Management</h3>

                <p>
                    Manage room allocation and occupancy records.
                </p>

            </div>

            <div class="feature">

                <h3>Payment Tracking</h3>

                <p>
                    Access payment records and monitor outstanding balances.
                </p>

            </div>

            <div class="feature">

                <h3>Notifications</h3>

                <p>
                    Receive important hostel announcements and updates.
                </p>

            </div>

        </div>

    </section>

    <!-- Footer -->

    <footer>

        <p>
            &copy; <?php echo date("Y"); ?>
            Maisha Hostel Management System.
        </p>

        <p>
            Your Comfort, Our Priority.
        </p>

    </footer>

</body>

</html>
