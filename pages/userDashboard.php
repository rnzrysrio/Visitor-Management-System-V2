<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.html");
    exit();
}

include('../sql/db.php');

// include('dbQueries/autoStatusManager.php'); // Include auto checkout manager

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    // $fetchAppointmentHistory = "SELECT * FROM appointments WHERE name='$name'";
    // $result = mysqli_query($conn, $fetchAppointmentHistory);
    // if ($result) {
    //     $appointments = mysqli_fetch_all($result, MYSQLI_ASSOC); // Fetch all appointments for the user
    // } else {
    //     echo "<script>alert('Failed to fetch appointment history!');</script>";
    // }
} else {
    header("Location: loginPage.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Visitor">
    <meta name="keywords" content="Visitor">
    <meta name="charset" content="UTF-8">
    <link rel="stylesheet" href="../assets/css/userDashboard.css">
    <title>VMS</title>
    <script src="../automation_scripts/script.js"></script>
</head>
<body>

    <div class="userHeader">
        <h1 id="userGreeting">Hello! <?php echo $_SESSION['name']?></h1>
        <div class="profile-dropdown">
            <button id="dropbtn" class="dropbtn" onclick="toggleProfile()">&#9662;</button>
            <div id="dropdown-content" class="dropdown-content">
                <a href="../sql/accountLogout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="appointments">
        <table>
            <caption>Appointment History</caption>
            <thead>
                <tr>
                    <th>Visit Date</th>
                    <th>Check In Time</th>
                    <th>Check Out Time</th>
                    <th>Purpose of Visit</th>
                    <th>Department</th>
                    <th>Visit Status</th>
                    <th>Approval Status</th>
                    <th>Download Digital Pass (For Approved Appointments)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Loop through the appointments and display them in the table
                if (isset($appointments) && !empty($appointments)) {
                    foreach ($appointments as $appointment) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($appointment['visit_date']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['checkin_time']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['checkout_time']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['purpose']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['department']) . "</td>";
                        if ($appointment['visit_status'] == 'checked-in') {
                            echo "<td class='checkInStatus' style='color: green;'>Checked-In</td>";
                        } else if ($appointment['visit_status'] == 'checked-out') {
                            echo "<td class='checkInStatus' style='color: red;'>Checked-Out</td>";
                        } else if ($appointment['visit_status'] == 'pending') {
                            echo "<td class='checkInStatus' style='color: orange;'>Pending</td>";
                        }
                        else if ($appointment['visit_status'] == 'cancelled') {
                            echo "<td class='checkInStatus' style='color: gray;'>Cancelled</td>";
                        } else {
                            echo "<td class='checkInStatus' style='color: gray;'>Unknown</td>";
                        }

                        if ($appointment['appointment_status'] == 'approved') {
                            echo "<td class='checkInStatus' style='color: green;'>Approved</td>";
                        } else if ($appointment['appointment_status'] == 'rejected') {
                            echo "<td class='checkInStatus' style='color: red;'>Rejected</td>";
                        } else if ($appointment['appointment_status'] == 'pending') {
                            echo "<td class='checkInStatus' style='color: orange;'>Pending</td>";
                        }
                        else{
                            echo "<td class='checkInStatus' style='color: gray;'>Unknown</td>";
                        }

                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No appointments found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <button onclick="toggleAppointmentModal()">+ Request Appointment</button>
    </div>

    <div id="modalOverlay" onclick="toggleAppointmentModal()"></div>

    <div class="appointmentModal" id="appointmentModal">
        <button class="exitModal" onclick="toggleAppointmentModal()">&times</button>
        <h1>Appointment Request Form</h1>
        <form action="../sql/userAddAppointment.php" method="post">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['fullname']) ?>" required readonly>

            <label for="email">Email:</label>
            <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($user['email']) ?>" required readonly>

            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone_number']) ?>" required readonly>

            <label for="visit-date">Visit Date:</label>
            <input type="date" id="visit-date" name="visit-date" required>

            <label for="checkin">Check In Time:</label>
            <select id="editCheckIn" name="checkin" required onchange="updateCheckoutTime()">
                    <option value="" disabled selected>Select Check In Time</option>
                    <option value="7:00 AM">7:00 AM</option>
                    <option value="1:00 PM">1:00 PM</option>
                    <option value="6:00 PM">6:00 PM</option>
            </select>

            <label for="checkout">Check Out Time:</label>
            <select id="editCheckOut" name="checkout" onchange="toggleCustomCheckoutTime()">
                    <option value="" disabled selected>Select Check Out Time</option>
                    <option value="9:00 AM">9:00 AM</option>
                    <option value="3:00 PM">3:00 PM</option>
                    <option value="8:00 PM">8:00 PM</option>
                    <option value="other">Other (Specify Time)</option>
            </select>

            <input type="text" id="customCheckOutTime" name="customCheckOutTime" placeholder="Enter custom check out time" style="display:none;">

            <label for="purpose">Purpose of Visit:</label>
            <textarea id="purpose" name="purpose"></textarea>

            <label for="department">Department:</label>
            <select name="department" id="department" required>
                <option value="" disabled selected>Select Department</option>
                <option value="HR1">Human Resource Department 1</option>
                <option value="HR2">Human Resource Department 2</option>
                <option value="LGSTCS1">Logistics Department 1</option>
                <option value="LGSTCS2">Logistics Department 2</option>
                <option value="CORE1">Core Transaction Deparment 1</option>
                <option value="CORE2">Core Transaction Department 2</option>
                <option value="ADMIN">Administrative Department</option>
                <option value="FINANCE">Financial Department</option>
            </select>

            <input type="hidden" id="encoder" name="encoder" value="<?php echo htmlspecialchars($user['fullname']) ?>">
            
            <button type="submit" value="submit">Submit Appointment</button>
        </form>
    </div>
</body>
</html>