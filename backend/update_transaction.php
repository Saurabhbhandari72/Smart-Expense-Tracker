
<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit();
}

// CSRF Security
if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    die("Invalid security token. Please refresh and try again.");
}

// Get form data
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$type = $_POST['type'] ?? '';
$category = trim($_POST['category'] ?? '');
$amount = $_POST['amount'] ?? '';
$description = trim($_POST['description'] ?? '');
$transaction_date = $_POST['transaction_date'] ?? '';

$user_id = $_SESSION['user_id'];

// Validate data
if (
    !$id ||
    !in_array($type, ['income', 'expense'], true) ||
    $category === '' ||
    !is_numeric($amount) ||
    (float)$amount <= 0 ||
    !is_finite((float)$amount) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $transaction_date)
) {
    die("Invalid transaction details.");
}

// Validate actual calendar date
$date = DateTime::createFromFormat('!Y-m-d', $transaction_date);

if (!$date || $date->format('Y-m-d') !== $transaction_date) {
    die("Invalid transaction date.");
}

try {

    $stmt = $conn->prepare("
        UPDATE transactions
        SET
            type = ?,
            category = ?,
            amount = ?,
            description = ?,
            transaction_date = ?
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([
        $type,
        $category,
        $amount,
        $description,
        $transaction_date,
        $id,
        $user_id
    ]);

    header("Location: ../dashboard.php?status=updated");
    exit();

} catch (PDOException $e) {

    error_log($e->getMessage());
    die("Unable to update transaction. Please try again.");

}

?>
