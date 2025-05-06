<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('db.php');
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($username === $user['username'] && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $username;
        $_SESSION['account_id'] = $user['account_id'];
        $_SESSION['role'] = $user['account_role'];
        $_SESSION['name'] = $user['fullname'];

        if ($user['account_role'] == 'admin') {
            header('Location: ../pages/adminDashboard.php');
        } else {
            header('Location: ../pages/userDashboard.php');
        }
    } else {
        $error_message = "Invalid username or password.";
        header('Location: ../index.html?error=' . urlencode($error_message));
        echo "<script>alert('Invalid username or password!');</script>";
    }
}
?>