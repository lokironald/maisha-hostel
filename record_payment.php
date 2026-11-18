
<?php
session_start();
include "database.php";

// Protect administrator page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$messageType = "";

// Get registered residents
$users_result = mysqli_query($conn,
    "SELECT id, fullname, email FROM users ORDER BY fullname ASC");

// Process payment
if (isset($_POST['record_payment'])) {

    $user_id = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $payment_method = trim($_POST['payment_method']);
    $reference = trim($_POST['transaction_reference']);

    if ($user_id <= 0 || $amount <= 0 || empty($payment_method)) {

        $message = "Please provide valid payment information.";
        $messageType = "error";

    } else {

        // Confirm resident exists
        $check = mysqli_prepare($conn,
            "SELECT id FROM users WHERE id = ?");

        mysqli_stmt_bind_param($check, "i", $user_id);
        mysqli_stmt_execute($check);

        $check_result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($check_result) === 0) {

            $message = "Selected resident does not exist.";
            $messageType = "error";

        } else {

            $admin_id = (int)$_SESSION['admin_id'];

            $stmt = mysqli_prepare($conn,
                "INSERT INTO payments
                (user_id, amount, payment_method,
                 transaction_reference, recorded_by)
                VALUES (?, ?, ?, ?, ?)");

            mysqli_stmt_bind_param(
                $stmt,
                "idssi",
                $user_id,
                $amount,
                $payment_method,
                $reference,
                $admin_id
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Payment recorded successfully!";
                $messageType = "success";

            } else {

                $message = "Unable to record payment.";
                $messageType = "error";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Record Payment - Maisha Hostel</title>

<link rel="stylesheet" href="style.css">

<style>

.payment-container {
    max-width: 650px;
    margin: 30px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
}

.payment-container h2 {
    color: #198754;
    text-align: center;
}

.payment-container label {
    display: block;
    margin-top: 18px;
    font-weight: bold;
}

.payment-container input,
.payment-container select {
    width: 100%;
    padding: 12px;
    margin-top: 7px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
}

.payment-container button {
    width: 100%;
    padding: 13px;
    margin-top: 25px;
    background: #198754;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
}

.payment-container button:hover {
    background: #146c43;
}

.message {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 6px;
    text-align: center;
}

.success {
    background: #d1e7dd;
    color: #0f5132;
}

.error {
    background: #f8d7da;
    color: #842029;
}

.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #198754;
    text-decoration: none;
}

</style>

</head>

<body>

<div class="payment-container">

    <h2>Record Hostel Payment</h2>

    <p>Enter the resident's payment details below.</p>

    <?php if (!empty($message)) { ?>

        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>

    <form method="POST">

        <label>Select Resident</label>

        <select name="user_id" required>

            <option value="">-- Select Resident --</option>

            <?php while ($resident = mysqli_fetch_assoc($users_result)) { ?>

                <option value="<?php echo (int)$resident['id']; ?>">

                    <?php
                    echo htmlspecialchars(
                        $resident['fullname'] . " - " . $resident['email']
                    );
                    ?>

                </option>

            <?php } ?>

        </select>


        <label>Amount Paid (UGX)</label>

        <input
            type="number"
            name="amount"
            min="1"
            step="0.01"
            placeholder="Enter amount paid"
            required
        >


        <label>Payment Method</label>

        <select name="payment_method" required>

            <option value="">-- Select Payment Method --</option>

            <option value="Cash">Cash</option>

            <option value="Mobile Money">Mobile Money</option>

            <option value="Bank Transfer">Bank Transfer</option>

        </select>


        <label>Transaction Reference (Optional)</label>

        <input
            type="text"
            name="transaction_reference"
            placeholder="Enter transaction reference"
        >


        <button type="submit" name="record_payment">
            Record Payment
        </button>

    </form>

    <a href="admin_dashboard.php" class="back-link">
        Back to Admin Dashboard
    </a>

</div>

</body>
</html>
