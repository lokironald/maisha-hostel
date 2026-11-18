
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

// Retrieve resident information
$user_sql = "SELECT fullname, email FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    die("Resident account not found.");
}

// Retrieve hostel fee from the resident's latest application
$fee_sql = "SELECT room_type
            FROM applications
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 1";

$fee_stmt = mysqli_prepare($conn, $fee_sql);
mysqli_stmt_bind_param($fee_stmt, "i", $user_id);
mysqli_stmt_execute($fee_stmt);
$fee_result = mysqli_stmt_get_result($fee_stmt);
$application = mysqli_fetch_assoc($fee_result);

$roomType = $application['room_type'] ?? 'Not Set';

if ($roomType == "Single Room") {
    $hostelFee = 500000;
} elseif ($roomType == "Shared Room") {
    $hostelFee = 350000;
} else {
    $hostelFee = 0;
}

// Retrieve resident payments
$payment_sql = "SELECT id, amount, payment_method,
                       transaction_reference, payment_date
                FROM payments
                WHERE user_id = ?
                ORDER BY id DESC";

$payment_stmt = mysqli_prepare($conn, $payment_sql);
mysqli_stmt_bind_param($payment_stmt, "i", $user_id);
mysqli_stmt_execute($payment_stmt);
$payment_result = mysqli_stmt_get_result($payment_stmt);

// Calculate total amount paid
$total_sql = "SELECT SUM(amount) AS total_paid
              FROM payments
              WHERE user_id = ?";

$total_stmt = mysqli_prepare($conn, $total_sql);
mysqli_stmt_bind_param($total_stmt, "i", $user_id);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_data = mysqli_fetch_assoc($total_result);

$totalPaid = (float)($total_data['total_paid'] ?? 0);

// Calculate outstanding balance
$balance = max(0, $hostelFee - $totalPaid);

// Determine payment status
if ($hostelFee <= 0) {
    $status = "Fee Not Set";
} elseif ($totalPaid >= $hostelFee) {
    $status = "Fully Paid";
} elseif ($totalPaid > 0) {
    $status = "Partially Paid";
} else {
    $status = "Not Paid";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Payments - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.payment-container {
    max-width: 1100px;
    margin: 30px auto;
    padding: 25px;
}

.payment-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 25px 0;
}

.summary-card {
    background: white;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.summary-card h3 {
    color: #555;
    font-size: 15px;
}

.summary-card p {
    color: #198754;
    font-size: 22px;
    font-weight: bold;
}

.payment-table {
    background: white;
    padding: 20px;
    border-radius: 10px;
    overflow-x: auto;
    margin-top: 25px;
}

.payment-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

.payment-table th,
.payment-table td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.payment-table th {
    background: #198754;
    color: white;
}

.status {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 5px;
    background: #e8f5e9;
    color: #198754;
    font-weight: bold;
}

.receipt-link {
    color: #198754;
    font-weight: bold;
    text-decoration: none;
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

@media print {
    .action-buttons,
    .receipt-link {
        display: none;
    }
}

</style>

</head>

<body>

<div class="payment-container">

    <h1>My Hostel Payments</h1>

    <p>
        Welcome,
        <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>
    </p>

    <p>
        Email: <?php echo htmlspecialchars($user['email']); ?>
    </p>

    <!-- PAYMENT SUMMARY -->

    <section class="payment-summary">

        <div class="summary-card">
            <h3>Hostel Room Type</h3>
            <p><?php echo htmlspecialchars($roomType); ?></p>
        </div>

        <div class="summary-card">
            <h3>Total Hostel Fee</h3>
            <p>UGX <?php echo number_format($hostelFee, 2); ?></p>
        </div>

        <div class="summary-card">
            <h3>Total Amount Paid</h3>
            <p>UGX <?php echo number_format($totalPaid, 2); ?></p>
        </div>

        <div class="summary-card">
            <h3>Outstanding Balance</h3>
            <p>UGX <?php echo number_format($balance, 2); ?></p>
        </div>

    </section>

    <h3>Payment Status</h3>

    <p class="status">
        <?php echo htmlspecialchars($status); ?>
    </p>

    <!-- PAYMENT HISTORY -->

    <div class="payment-table">

        <h2>My Payment History</h2>

        <table>

            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Amount (UGX)</th>
                    <th>Payment Method</th>
                    <th>Transaction Reference</th>
                    <th>Payment Date</th>
                    <th>Receipt</th>
                </tr>
            </thead>

            <tbody>

                <?php if (mysqli_num_rows($payment_result) > 0) { ?>

                    <?php while ($payment = mysqli_fetch_assoc($payment_result)) { ?>

                        <tr>

                            <td><?php echo (int)$payment['id']; ?></td>

                            <td>
                                <?php echo number_format((float)$payment['amount'], 2); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($payment['payment_method']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars(
                                    $payment['transaction_reference'] ?: 'N/A'
                                ); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($payment['payment_date']); ?>
                            </td>

                            <td>
                                <a class="receipt-link"
                                   href="resident_payment_receipt.php?id=<?php echo (int)$payment['id']; ?>">
                                    View Receipt
                                </a>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="6">
                            You have no recorded payments yet.
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

    <div class="action-buttons">

        <button onclick="window.print()">Print My Payment Summary</button>

        <a href="dashboard.php">Back to Dashboard</a>

    </div>

</div>

</body>
</html>
