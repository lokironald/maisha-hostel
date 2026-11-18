
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Retrieve residents, their room type and payment totals
$sql = "SELECT
            users.id,
            users.fullname,
            users.email,
            applications.room_type,
            CASE
                WHEN applications.room_type = 'Single Room' THEN 500000
                WHEN applications.room_type = 'Shared Room' THEN 350000
                ELSE 0
            END AS hostel_fee,
            COALESCE(payment_totals.total_paid, 0) AS total_paid
        FROM users

        LEFT JOIN applications
        ON users.id = applications.user_id

        LEFT JOIN (
            SELECT user_id, SUM(amount) AS total_paid
            FROM payments
            GROUP BY user_id
        ) AS payment_totals
        ON users.id = payment_totals.user_id

        ORDER BY users.fullname ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database error: " . htmlspecialchars(mysqli_error($conn)));
}

// Summary totals
$totalResidents = 0;
$totalFees = 0;
$totalPayments = 0;
$totalBalances = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Resident Fee Balances - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.balance-container {
    background: white;
    padding: 25px;
    margin: 25px auto;
    border-radius: 10px;
    overflow-x: auto;
}

.balance-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 20px;
    margin: 25px 0;
}

.summary-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 5px solid #198754;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.summary-card h3 {
    color: #555;
    font-size: 14px;
}

.summary-card p {
    color: #198754;
    font-size: 22px;
    font-weight: bold;
}

.balance-container table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

.balance-container th,
.balance-container td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.balance-container th {
    background: #198754;
    color: white;
}

.paid {
    color: green;
    font-weight: bold;
}

.partial {
    color: orange;
    font-weight: bold;
}

.unpaid {
    color: red;
    font-weight: bold;
}

.action-buttons {
    margin: 20px 0;
}

.action-buttons a,
.action-buttons button {
    display: inline-block;
    padding: 11px 18px;
    margin: 5px;
    background: #198754;
    color: white;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
}

.action-buttons a:hover,
.action-buttons button:hover {
    background: #146c43;
}

@media print {

    .action-buttons {
        display: none;
    }

    body {
        background: white;
    }

}

</style>

</head>

<body>

<div class="container">

    <h1>Maisha Hostel Resident Fee Balances</h1>

    <p>Resident payment and outstanding balance report.</p>


    <!-- ACTION BUTTONS -->

    <div class="action-buttons">

        <button onclick="window.print()">
            Print Balance Report
        </button>

        <a href="record_payment.php">
            Record Payment
        </a>

        <a href="payment_history.php">
            Payment History
        </a>

        <a href="admin_dashboard.php">
            Admin Dashboard
        </a>

    </div>


    <!-- RESIDENT BALANCE TABLE -->

    <div class="balance-container">

        <h2>Resident Payment Details</h2>

        <table>

            <thead>

                <tr>
                    <th>Resident ID</th>
                    <th>Resident Name</th>
                    <th>Room Type</th>
                    <th>Hostel Fee (UGX)</th>
                    <th>Total Paid (UGX)</th>
                    <th>Balance (UGX)</th>
                    <th>Payment Status</th>
                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0) { ?>

                    <?php while ($resident = mysqli_fetch_assoc($result)) { ?>

                        <?php

                        $hostelFee = (float)$resident['hostel_fee'];
                        $totalPaid = (float)$resident['total_paid'];

                        $balance = max(0, $hostelFee - $totalPaid);

                        if ($hostelFee <= 0) {
                            $status = "Fee Not Set";
                            $statusClass = "";
                        } elseif ($totalPaid >= $hostelFee) {
                            $status = "Paid";
                            $statusClass = "paid";
                        } elseif ($totalPaid > 0) {
                            $status = "Partially Paid";
                            $statusClass = "partial";
                        } else {
                            $status = "Not Paid";
                            $statusClass = "unpaid";
                        }

                        $totalResidents++;
                        $totalFees += $hostelFee;
                        $totalPayments += $totalPaid;
                        $totalBalances += $balance;

                        ?>

                        <tr>

                            <td>
                                <?php echo (int)$resident['id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($resident['fullname']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars(
                                    $resident['room_type'] ?? 'Not assigned'
                                ); ?>
                            </td>

                            <td>
                                <?php echo number_format($hostelFee, 2); ?>
                            </td>

                            <td>
                                <?php echo number_format($totalPaid, 2); ?>
                            </td>

                            <td>
                                <?php echo number_format($balance, 2); ?>
                            </td>

                            <td class="<?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="7">No registered residents found.</td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>


    <!-- FINANCIAL SUMMARY -->

    <section class="balance-summary">

        <div class="summary-card">
            <h3>Total Residents</h3>
            <p><?php echo $totalResidents; ?></p>
        </div>

        <div class="summary-card">
            <h3>Total Hostel Fees</h3>
            <p>UGX <?php echo number_format($totalFees, 2); ?></p>
        </div>

        <div class="summary-card">
            <h3>Total Payments</h3>
            <p>UGX <?php echo number_format($totalPayments, 2); ?></p>
        </div>

        <div class="summary-card">
            <h3>Total Outstanding</h3>
            <p>UGX <?php echo number_format($totalBalances, 2); ?></p>
        </div>

    </section>

</div>

</body>
</html>
