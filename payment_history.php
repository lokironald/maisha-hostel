
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Retrieve payment records
$sql = "SELECT
            payments.id,
            payments.amount,
            payments.payment_method,
            payments.transaction_reference,
            payments.payment_date,
            users.fullname,
            users.email
        FROM payments
        INNER JOIN users
        ON payments.user_id = users.id
        ORDER BY payments.id DESC";

$result = mysqli_query($conn, $sql);

// Calculate total payments
$total_sql = "SELECT SUM(amount) AS total_paid FROM payments";
$total_result = mysqli_query($conn, $total_sql);
$total_data = mysqli_fetch_assoc($total_result);

$totalPaid = $total_data['total_paid'] ?? 0;

// Count payment records
$count_sql = "SELECT COUNT(*) AS total FROM payments";
$count_result = mysqli_query($conn, $count_sql);
$count_data = mysqli_fetch_assoc($count_result);

$totalRecords = (int)$count_data['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Payment History - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.payment-history {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin: 25px auto;
    overflow-x: auto;
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
    border-left: 5px solid #198754;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.summary-card h3 {
    color: #555;
    font-size: 15px;
}

.summary-card p {
    color: #198754;
    font-size: 25px;
    font-weight: bold;
}

.payment-history table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

.payment-history th,
.payment-history td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.payment-history th {
    background: #198754;
    color: white;
}

.payment-history tr:hover {
    background: #f5f5f5;
}

.receipt-link {
    display: inline-block;
    padding: 7px 12px;
    background: #198754;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.receipt-link:hover {
    background: #146c43;
}

.action-buttons {
    margin: 20px 0;
}

.action-buttons button,
.action-buttons a {
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

.action-buttons button:hover,
.action-buttons a:hover {
    background: #146c43;
}

@media print {

    .action-buttons,
    .receipt-link {
        display: none;
    }

    body {
        background: white;
    }

    .payment-history {
        box-shadow: none;
    }

}

</style>

</head>

<body>

<div class="container">

    <h1>Maisha Hostel Payment History</h1>

    <p>Administrator Payment Records</p>

    <!-- PAYMENT SUMMARY -->

    <section class="payment-summary">

        <div class="summary-card">

            <h3>Total Payments Received</h3>

            <p>
                UGX <?php echo number_format((float)$totalPaid, 2); ?>
            </p>

        </div>

        <div class="summary-card">

            <h3>Total Payment Records</h3>

            <p><?php echo $totalRecords; ?></p>

        </div>

    </section>

    <!-- ACTION BUTTONS -->

    <div class="action-buttons">

        <button onclick="window.print()">
            Print Payment History
        </button>

        <a href="record_payment.php">
            Record New Payment
        </a>

        <a href="admin_dashboard.php">
            Admin Dashboard
        </a>

    </div>

    <!-- PAYMENT TABLE -->

    <div class="payment-history">

        <h2>All Recorded Payments</h2>

        <table>

            <thead>

                <tr>
                    <th>Payment ID</th>
                    <th>Resident Name</th>
                    <th>Email</th>
                    <th>Amount (UGX)</th>
                    <th>Payment Method</th>
                    <th>Transaction Reference</th>
                    <th>Payment Date</th>
                    <th>Receipt</th>
                </tr>

            </thead>

            <tbody>

                <?php if ($result && mysqli_num_rows($result) > 0) { ?>

                    <?php while ($payment = mysqli_fetch_assoc($result)) { ?>

                        <tr>

                            <td>
                                <?php echo (int)$payment['id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($payment['fullname']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($payment['email']); ?>
                            </td>

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
                                   href="payment_receipt.php?id=<?php echo (int)$payment['id']; ?>">
                                    View Receipt
                                </a>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="8">
                            No payment records available.
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>
