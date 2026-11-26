
<?php

require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid Request");
}

$full_name = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";

// Validate fields
if (
    $full_name === "" ||
    $email === "" ||
    $username === "" ||
    $password === ""
) {
    exit("Please fill all fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Invalid email address.");
}

if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
    exit("Username must be 3-50 characters and contain only letters, numbers or underscore.");
}

if (strlen($password) < 8) {
    exit("Password must contain at least 8 characters.");
}

if ($password !== $confirm_password) {
    exit("Passwords do not match.");
}

// Secure password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {

    $sql = "INSERT INTO users
            (full_name, email, username, password)
            VALUES (:name, :email, :username, :password)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':name' => $full_name,
        ':email' => $email,
        ':username' => $username,
        ':password' => $hashed_password
    ]);

    echo "
    <div style='
        background:#080f20;
        color:white;
        min-height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        font-family:Arial;
        text-align:center;
    '>
        <div>
            <h1 style='color:#22c55e;'>Account Created!</h1>
            <p>Your account has been registered successfully.</p>
            <br>
            <a href='../login.html'
               style='color:#60a5fa;'>
               Go to Login
            </a>
        </div>
    </div>
    ";


} catch (PDOException $e) {

    echo "<pre>";
    echo "DATABASE ERROR:\n";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";

}


?>
