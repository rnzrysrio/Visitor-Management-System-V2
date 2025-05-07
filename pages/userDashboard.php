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

    $stmt2 = $conn->prepare("SELECT ua.fullname, ap_vr.appointment_id, ap_vr.visit_date, ap_vr.check_in_time, ap_vr.check_out_time, ap_vr.purpose_of_visit, ap_vr.go_to_department, ap_vr.visit_status, ap_vr.approval_status FROM user_accounts ua INNER JOIN ( SELECT aa.appointment_id, aa.account_id, aa.visit_date, aa.check_in_time, aa.check_out_time, aa.purpose_of_visit, aa.go_to_department, aa.visit_status, vr.approval_status FROM all_appointments aa INNER JOIN visit_request vr ON aa.appointment_id = vr.appointment_id ) AS ap_vr ON ua.account_id = ap_vr.account_id WHERE ua.account_id = ?");
    $stmt2->bind_param("i", $user['account_id']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2) {
        $appointments = $result2->fetch_all(MYSQLI_ASSOC);
    } else {
        echo "<script>alert('Failed to fetch appointment history!');</script>";
    }
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
                    <th>Download Pass</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Loop through the appointments and display them in the table
                if (isset($appointments) && !empty($appointments)) {
                    foreach ($appointments as $appointment) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($appointment['visit_date']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['check_in_time']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['check_out_time']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['purpose_of_visit']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['go_to_department']) . "</td>";
                        if ($appointment['visit_status'] == 'checked-in') {
                            echo "<td>Checked-In</td>";
                        } else if ($appointment['visit_status'] == 'checked-out') {
                            echo "<td>Checked-Out</td>";
                        } else if ($appointment['visit_status'] == 'pending') {
                            echo "<td>Pending</td>";
                        }
                        else if ($appointment['visit_status'] == 'cancelled') {
                            echo "<td>Cancelled</td>";
                        } else {
                            echo "<td>Unknown</td>";
                        }

                        if ($appointment['approval_status'] == 'approved') {
                            echo "<td>Approved</td>";
                        } else if ($appointment['approval_status'] == 'denied') {
                            echo "<td>Denied</td>";
                        } else if ($appointment['approval_status'] == 'pending') {
                            echo "<td>Pending</td>";
                        }
                        else{
                            echo "<td>Unknown</td>";
                        }
                        
                        if ($appointment['approval_status'] == 'approved') {
                            echo "<td class='downloadPass'>
                                <form action='../sql/generatePass.php' method='POST' target='_blank'>
                                    <input type='hidden' name='appointment_id' value='" . htmlspecialchars($appointment['appointment_id']) . "'>
                                    <button type='submit'>Pass</button>
                                </form>
                            </td>";
                        } else {
                            echo "<td>Need Approval</td>";
                        }                        

                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No appointments found.</td></tr>";
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