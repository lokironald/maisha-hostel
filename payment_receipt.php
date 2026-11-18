
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get payment ID
$payment_id = (int)($_GET['id'] ?? 0);

if ($payment_id <= 0) {
    die("Invalid payment receipt ID.");
}

// Retrieve payment and resident details
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
        WHERE payments.id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $payment_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$payment = mysqli_fetch_assoc($result);

if (!$payment) {
    die("Payment record not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Payment Receipt - Maisha Hostel</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f4f6f5;
    margin: 0;
    padding: 30px;
}

.receipt {
    max-width: 650px;
    margin: auto;
    background: white;
    padding: 35px;
    border: 2px solid #198754;
    border-radius: 10px;
}

.receipt-header {
    text-align: center;
    border-bottom: 2px solid #198754;
    padding-bottom: 20px;
}

.receipt-header h1 {
    color: #198754;
    margin-bottom: 5px;
}

.receipt-header p {
    color: #555;
}

.receipt-title {
    text-align: center;
    margin: 25px 0;
    color: #198754;
}

.receipt-details {
    margin-top: 20px;
}

.receipt-details table {
    width: 100%;
    border-collapse: collapse;
}

.receipt-details td {
    padding: 13px 8px;
    border-bottom: 1px solid #ddd;
}

.receipt-details td:first-child {
    font-weight: bold;
    width: 40%;
}

.amount {
    color: #198754;
    font-size: 22px;
    font-weight: bold;
}

.receipt-footer {
    text-align: center;
    margin-top: 30px;
    border-top: 1px dashed #999;
    padding-top: 20px;
}

.receipt-actions {
    text-align: center;
    margin-top: 25px;
}

.receipt-actions button,
.receipt-actions a {
    display: inline-block;
    padding: 12px 20px;
    margin: 5px;
    border: none;
    border-radius: 5px;
    background: #198754;
    color: white;
    text-decoration: none;
    cursor: pointer;
}

@media print {

    body {
        background: white;
        padding: 0;
    }

    .receipt {
        border: 2px solid #198754;
        box-shadow: none;
    }

    .receipt-actions {
        display: none;
    }

}

</style>

</head>

<body>

<div class="receipt">

    <div class="receipt-header">

        <h1>MAISHA HOSTEL</h1>

        <p>HOSTEL MANAGEMENT SYSTEM</p>

        <p>OFFICIAL PAYMENT RECEIPT</p>

    </div>


    <h2 class="receipt-title">PAYMENT RECEIPT</h2>


    <div class="receipt-details">

        <table>

            <tr>
                <td>Receipt Number:</td>
                <td>
                    MH-<?php echo str_pad($payment['id'], 5, '0', STR_PAD_LEFT); ?>
                </td>
            </tr>

            <tr>
                <td>Resident Name:</td>
                <td>
                    <?php echo htmlspecialchars($payment['fullname']); ?>
                </td>
            </tr>

            <tr>
                <td>Email Address:</td>
                <td>
                    <?php echo htmlspecialchars($payment['email']); ?>
                </td>
            </tr>

            <tr>
                <td>Amount Paid:</td>
                <td class="amount">
                    UGX <?php echo number_format((float)$payment['amount'], 2); ?>
                </td>
            </tr>

            <tr>
                <td>Payment Method:</td>
                <td>
                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                </td>
            </tr>

            <tr>
                <td>Transaction Reference:</td>
                <td>
                    <?php echo htmlspecialchars(
                        $payment['transaction_reference'] ?: 'N/A'
                    ); ?>
                </td>
            </tr>

            <tr>
                <td>Payment Date:</td>
                <td>
                    <?php echo htmlspecialchars($payment['payment_date']); ?>
                </td>
            </tr>

        </table>

    </div>


    <div class="receipt-footer">

        <h3>Thank You!</h3>

        <p>Thank you for making your hostel payment.</p>

        <p>Keep this receipt for your records.</p>

        <p><strong>Maisha Hostel Management</strong></p>

    </div>


    <div class="receipt-actions">

        <button onclick="window.print()">
            Print Receipt
        </button>

        <a href="payment_history.php">
            Back to Payment History
        </a>

    </div>

</div>

</body>
</html>
