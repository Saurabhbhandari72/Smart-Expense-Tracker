
<?php

session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid Request");
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'] ?? '';
$category = trim($_POST['category'] ?? '');
$amount = $_POST['amount'] ?? '';
$description = trim($_POST['description'] ?? '');
$date = $_POST['transaction_date'] ?? '';

$valid_types = ['income', 'expense'];

if (!in_array($type, $valid_types, true)) {
    exit("Invalid transaction type.");
}

if ($category === '' || !is_numeric($amount) ||
    (float)$amount <= 0 || (float)$amount > 99999999.99) {
    exit("Please enter valid transaction details.");
}

$date_object = DateTime::createFromFormat('!Y-m-d', $date);

if (!$date_object || $date_object->format('Y-m-d') !== $date) {
    exit("Invalid date.");
}

try {

    $sql = "INSERT INTO transactions
            (user_id, type, category, amount, description, transaction_date)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $user_id,
        $type,
        $category,
        $amount,
        $description,
        $date
    ]);

    header("Location: ../dashboard.php?success=1");
    exit();

} catch (PDOException $e) {

    error_log($e->getMessage());
    exit("Unable to save transaction. Please try again.");

}

?>
