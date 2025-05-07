<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('db.php');

    $username = $_POST['username'];
    $password = $_POST['password'];
    $fullname = $_POST['name'];
    $birthdate = $_POST['birthday'];
    $homeAddress = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $accountRegistrationDate = date('Y-m-d');

    // Check if username already exists
    $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
                alert('Username already taken. Please choose a different one.');
                window.location.href = '../register.php';
              </script>";
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
                alert('Email already registered. Please use a different email.');
                window.location.href = '../register.php';
              </script>";
        exit();
    }

    // Check if phone number already exists
    $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE phone_number = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
                alert('Phone number already registered. Please use a different phone number.');
                window.location.href = '../register.php';
              </script>";
        exit();
    }

    // If no duplicates found, proceed with registration
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO user_accounts (username, password, fullname, birthdate, home_address, phone_number, email, account_creation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $username, $hashedPassword, $fullname, $birthdate, $homeAddress, $phone, $email, $accountRegistrationDate);

    if ($stmt->execute()) {
        echo "<script>
                alert('Registration Successful!');
                if (confirm('Would you like to log in now?')) {
                    window.location.href = '../index.html';
                } else {
                    window.location.href = '../register.php';
                }
              </script>";
    } else {
        echo "<script>alert('Registration Failed!');</script>";
    }
}
?>