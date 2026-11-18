
<?php
session_start();
include "database.php";

// Protect resident receipt page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$payment_id = (int)($_GET['id'] ?? 0);

if ($payment_id <= 0) {
    die("Invalid payment receipt.");
}

// Retrieve only the logged-in resident's payment
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
        WHERE payments.id = ?
        AND payments.user_id = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $payment_id, $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$payment = mysqli_fetch_assoc($result);

if (!$payment) {
    die("Receipt not found or you are not authorized to view it.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Payment Receipt - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.receipt-container {
    max-width: 700px;
    margin: 40px auto;
    padding: 30px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
}

.receipt-header {
    text-align: center;
    border-bottom: 2px solid #198754;
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.receipt-header h1 {
    color: #198754;
}

.receipt-details {
    margin: 25px 0;
}

.receipt-details p {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

.receipt-details strong {
    display: inline-block;
    width: 190px;
}

.receipt-footer {
    text-align: center;
    margin-top: 30px;
    color: #555;
}

.receipt-buttons {
    text-align: center;
    margin-top: 25px;
}

.receipt-buttons button,
.receipt-buttons a {
    display: inline-block;
    padding: 12px 20px;
    margin: 5px;
    background: #198754;
    color: white;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
}

.receipt-buttons button:hover,
.receipt-buttons a:hover {
    background: #146c43;
}

@media print {

    .receipt-buttons {
        display: none;
    }

    .receipt-container {
        box-shadow: none;
        margin: 0 auto;
    }

    body {
        background: white;
    }

}

</style>

</head>

<body>

<div class="receipt-container">

    <div class="receipt-header">

        <h1>MAISHA HOSTEL</h1>

        <h2>OFFICIAL PAYMENT RECEIPT</h2>

        <p>Resident Payment Confirmation</p>

    </div>

    <div class="receipt-details">

        <p>
            <strong>Receipt Number:</strong>
            MH-<?php echo str_pad((string)$payment['id'], 5, "0", STR_PAD_LEFT); ?>
        </p>

        <p>
            <strong>Resident Name:</strong>
            <?php echo htmlspecialchars($payment['fullname']); ?>
        </p>

        <p>
            <strong>Email Address:</strong>
            <?php echo htmlspecialchars($payment['email']); ?>
        </p>

        <p>
            <strong>Amount Paid:</strong>
            UGX <?php echo number_format((float)$payment['amount'], 2); ?>
        </p>

        <p>
            <strong>Payment Method:</strong>
            <?php echo htmlspecialchars($payment['payment_method']); ?>
        </p>

        <p>
            <strong>Transaction Reference:</strong>
            <?php echo htmlspecialchars($payment['transaction_reference'] ?: 'N/A'); ?>
        </p>

        <p>
            <strong>Payment Date:</strong>
            <?php echo htmlspecialchars($payment['payment_date']); ?>
        </p>

    </div>

    <div class="receipt-footer">

        <h3>Thank You!</h3>

        <p>
            Thank you for making your hostel payment.
        </p>

        <p>
            This receipt confirms the payment recorded in Maisha Hostel.
        </p>

    </div>

    <div class="receipt-buttons">

        <button onclick="window.print()">
            Print Receipt
        </button>

        <a href="my_payments.php">
            Back to My Payments
        </a>

    </div>

</div>

</body>
</html>
