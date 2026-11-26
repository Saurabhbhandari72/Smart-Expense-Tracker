
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$type = $_GET['type'] ?? 'expense';

if (!in_array($type, ['income', 'expense'], true)) {
    $type = 'expense';
}

require_once "backend/config.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Transaction | Smart Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">

        <div class="logo">💰</div>

        <h1><?= $type === 'income' ? 'Add Income' : 'Add Expense' ?></h1>
        <p class="subtitle">Enter your transaction details.</p>

        <form action="backend/save-transaction.php" method="POST">

            <input type="hidden" name="type"
                   value="<?= htmlspecialchars($type) ?>">

            <div class="form-group">
                <label>Category</label>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <?php
                    $categories = $type === 'income'
                        ? ['Salary', 'Business', 'Freelance', 'Gift', 'Other']
                        : ['Food', 'Shopping', 'Travel', 'Education',
                           'Bills', 'Health', 'Entertainment', 'Other'];

                    foreach ($categories as $category):
                    ?>
                        <option value="<?= htmlspecialchars($category) ?>">
                            <?= htmlspecialchars($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Amount (₹)</label>
                <input type="number" name="amount"
                       placeholder="Enter amount"
                       min="0.01" max="99999999.99"
                       step="0.01" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description"
                       placeholder="Enter description"
                       maxlength="255">
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name="transaction_date"
                       value="<?= date('Y-m-d') ?>" required>
            </div>

            <button type="submit" class="auth-btn">
                Save Transaction
            </button>

        </form>

        <p class="login-link">
            <a href="dashboard.php">← Back to Dashboard</a>
        </p>

    </div>
</div>

</body>
</html>
