<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Visitor">
    <meta name="keywords" content="Visitor">
    <meta name="charset" content="UTF-8">
    <link rel="stylesheet" href="assets/css/register.css">
    <title>VMS</title>
    <script src="automation_scripts/script.js"></script>
</head>
<body>
    <div class="registration-container">
        <h1>Registration</h1>
        <form action="sql/accountRegister.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required minlength="8"
                title="Password must be at least 8 characters long.">

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="birthday">Birthdate:</label>
            <input type="date" id="birthday" name="birthday" required>

            <label for="address">Home Address:</label>
            <input type="text" id="address" name="address" required>

            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <button type="submit" value="submit">Register</button>
        </form>
        <div class="login-link">
            <p>Already have an account? <a href="index.html">Login here</a></p>
        </div>
    </div>
</body>
</html>