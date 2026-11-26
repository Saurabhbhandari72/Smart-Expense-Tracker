
<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

require_once "config.php";

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit();
}

// CSRF verification
if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    die("Invalid security token. Please refresh the dashboard.");
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: ../dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {

    $stmt = $conn->prepare("
        DELETE FROM transactions
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$id, $user_id]);

    if ($stmt->rowCount() > 0) {
        header("Location: ../dashboard.php?status=deleted");
        exit();
    }

    header("Location: ../dashboard.php?status=notfound");
    exit();

} catch (PDOException $e) {

    error_log($e->getMessage());
    die("Unable to delete transaction. Please try again.");

}

?>
