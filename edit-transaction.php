
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once "backend/config.php";

$user_id = $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    die("Invalid transaction ID.");
}

$stmt = $conn->prepare("
    SELECT * FROM transactions
    WHERE id = ? AND user_id = ?
");

$stmt->execute([$id, $user_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    die("Transaction not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction | SmartTrack</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="main-content">

    <section class="transactions-section">

        <h2>Edit Transaction</h2>

        <form action="backend/update_transaction.php" method="POST">

            <input type="hidden" name="id"
                   value="<?= (int)$transaction['id'] ?>">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <label>Transaction Type</label>

            <select name="type" required>
                <option value="income"
                    <?= $transaction['type'] === 'income' ? 'selected' : '' ?>>
                    Income
                </option>

                <option value="expense"
                    <?= $transaction['type'] === 'expense' ? 'selected' : '' ?>>
                    Expense
                </option>
            </select>

            <br><br>

            <label>Category</label>

            <input type="text" name="category"
                   value="<?= htmlspecialchars($transaction['category']) ?>"
                   required>

            <br><br>

            <label>Amount</label>

            <input type="number" name="amount"
                   value="<?= htmlspecialchars($transaction['amount']) ?>"
                   min="0.01" step="0.01" required>

            <br><br>

            <label>Description</label>

            <textarea name="description"><?= htmlspecialchars($transaction['description'] ?? '') ?></textarea>

            <br><br>

            <label>Date</label>

            <input type="date" name="transaction_date"
                   value="<?= htmlspecialchars($transaction['transaction_date']) ?>"
                   required>

            <br><br>

            <button type="submit">Update Transaction</button>

            <a href="dashboard.php">Cancel</a>

<div class="edit-container">

    <div class="edit-card">

        <h2>✏️ Edit Transaction</h2>
        <p>Update your income or expense details.</p>

        <form action="backend/update_transaction.php"
              method="POST"
              class="edit-form">

            <!-- अपने पुराने सभी hidden inputs और form fields यहाँ रहने दो -->

            <div class="edit-buttons">
                <button type="submit" class="update-btn">
                    Update Transaction
                </button>

                <a href="dashboard.php" class="cancel-btn">
                    Cancel
                </a>
            </div>

        </form>

    </div>

</div>


        </form>

    </section>

</div>

</body>
</html>
