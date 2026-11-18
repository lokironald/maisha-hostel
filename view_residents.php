
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Retrieve residents and their application details
$sql = "SELECT
            users.id,
            users.fullname,
            users.email,
            applications.phone,
            applications.course,
            applications.year_of_study,
            applications.room_type,
            applications.status

        FROM users

        LEFT JOIN applications
        ON users.id = applications.user_id

        WHERE users.fullname LIKE ?
        OR users.email LIKE ?

        ORDER BY users.id DESC";

$stmt = mysqli_prepare($conn, $sql);

$searchTerm = "%" . $search . "%";

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $searchTerm,
    $searchTerm
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Residents - Maisha Hostel</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .residents-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .resident-search {
            margin: 20px 0;
        }

        .resident-search input {
            padding: 12px;
            width: 280px;
            max-width: 100%;
        }

        .resident-search button {
            padding: 12px 20px;
            background: #198754;
            color: white;
            border: none;
            cursor: pointer;
        }

        .resident-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .resident-table th,
        .resident-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .resident-table th {
            background: #198754;
            color: white;
        }

        .resident-table tr:nth-child(even) {
            background: #f2f2f2;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .status-approved {
            color: green;
            font-weight: bold;
        }

        .status-pending {
            color: #b8860b;
            font-weight: bold;
        }

        .status-rejected {
            color: red;
            font-weight: bold;
        }

        .not-applied {
            color: #666;
            font-weight: bold;
        }

    </style>

</head>

<body>

<div class="residents-container">

    <h1>Maisha Hostel Resident Management</h1>

    <h3>
        Welcome,
        <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!
    </h3>

    <h2>Registered Residents</h2>

    <p>
        Search for residents and view their accommodation
        application information.
    </p>

    <form method="GET" class="resident-search">

        <input
            type="text"
            name="search"
            placeholder="Enter resident name or email"
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit">Search</button>

        <a href="view_residents.php">Clear Search</a>

    </form>

    <div class="table-wrapper">

        <table class="resident-table">

            <thead>

                <tr>
                    <th>Resident ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Telephone</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Room Type</th>
                    <th>Application Status</th>
                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0) { ?>

                    <?php while ($resident = mysqli_fetch_assoc($result)) { ?>

                        <tr>

                            <td>
                                <?php echo (int) $resident['id']; ?>
                            </td>

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
                                <?php echo htmlspecialchars($resident['room_type'] ?? 'Not selected'); ?>
                            </td>

                            <td>

                                <?php
                                $status = $resident['status'];

                                if ($status === 'Approved') {
                                    echo '<span class="status-approved">Approved</span>';
                                } elseif ($status === 'Pending') {
                                    echo '<span class="status-pending">Pending</span>';
                                } elseif ($status === 'Rejected') {
                                    echo '<span class="status-rejected">Rejected</span>';
                                } else {
                                    echo '<span class="not-applied">Not Applied</span>';
                                }
                                ?>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="8">
                            No matching residents found.
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

    <?php mysqli_stmt_close($stmt); ?>

    <br>

    <a href="admin_dashboard.php">
        Back to Admin Dashboard
    </a>

    <br><br>

    <a href="admin_logout.php">
        Admin Logout
    </a>

</div>

</body>
</html>
