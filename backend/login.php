
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


session_start();

require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid Request");
}

$login = trim($_POST["login"] ?? "");
$password = $_POST["password"] ?? "";

if ($login === "" || $password === "") {
    exit("Please fill all fields.");
}

$sql = "SELECT * FROM users
        WHERE username = :login OR email = :login
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([':login' => $login]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['username'] = $user['username'];

    header("Location: ../dashboard.php");
    exit();

} else {

    exit("Invalid Username or Password.");

}

?>
