<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: loginPage.php");
    exit();
}

include('../sql/db.php');
// include('dbQueries/autoStatusManager.php');

$appointments = [];
$searchName = "";

// Handle search submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchName'])) {
    $_SESSION['searchName'] = trim($_POST['searchName']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// After redirect (GET request), check if search was stored
if (isset($_SESSION['searchName']) && $_SESSION['searchName'] !== "") {
    $searchName = mysqli_real_escape_string($conn, $_SESSION['searchName']);
    
    // Search for appointments based on the visitor's name
    $stmt = $conn->prepare("SELECT ua.fullname, ua.email, ua.phone_number, ap.appointment_id, ap.visit_date, ap.check_in_time, ap.check_out_time, ap.purpose_of_visit, ap.go_to_department, ap.visit_status, vr.approval_status FROM user_accounts ua JOIN all_appointments ap ON ua.account_id = ap.account_id JOIN visit_request vr ON ap.appointment_id = vr.appointment_id WHERE ua.fullname LIKE ?;");
    $searchTerm = "%" . $searchName . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        echo "<script>alert('Failed to fetch appointment history!');</script>";
    }

} else {
    //Display everything if no search term is provided
    $stmt2 = $conn->prepare("SELECT ua.fullname, ua.email, ua.phone_number, ap.appointment_id, ap.visit_date, ap.check_in_time, ap.check_out_time, ap.purpose_of_visit, ap.go_to_department, ap.visit_status, vr.approval_status FROM user_accounts ua JOIN all_appointments ap ON ua.account_id = ap.account_id JOIN visit_request vr ON ap.appointment_id = vr.appointment_id;");
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2) {
        $appointments = mysqli_fetch_all($result2, MYSQLI_ASSOC);
    } else {
        echo "<script>alert('Failed to fetch appointment history!');</script>";
    }
    unset($_SESSION['searchName']); // Reset session
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Visitor Management System">
    <meta name="keywords" content="Visitor, Management, System, VMS">
    <meta name="charset" content="UTF-8">
    <link rel="stylesheet" href="../assets/css/manageVisitorPage.css">
    <script src="../automation_scripts/script.js"></script>
    <title>VMS</title>
</head>
<body>
    <div class="userHeader">
        <button class="backBtn"><a href="adminDashboard.php">&lt Back</a></button>
        <h1 id="userGreeting">Admin: <?php echo $_SESSION['name']?></h1>
        <div class="profile-dropdown">
            <button id="dropbtn" class="dropbtn" onclick="toggleProfile()">&#9662;</button>
            <div id="dropdown-content" class="dropdown-content">
                <a href="../sql/accountLogout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="selectAndEditVisitor">
        <div class="searchVisitor">
            <form method="post">
                <label for="searchName">Search for a Visitor:</label>
                <input type="text" id="searchName" name="searchName" placeholder="Enter visitor name" value="<?php echo $searchName; ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="appointments">
            <table>
                <caption>Appointment History</caption>
                <thead>
                    <tr>
                        <th>Visitor Name</th>
                        <th>Visit Date</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                        <th>Visit Status</th>
                        <th>Approval Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($appointments)) {
                        foreach ($appointments as $appointment) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($appointment['fullname']) . "</td>";
                            echo "<td>" . htmlspecialchars($appointment['visit_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($appointment['check_in_time']) . "</td>";
                            echo "<td>" . htmlspecialchars($appointment['check_out_time']) . "</td>";
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

                            echo "<td id='actionCell'><button class='actionBtn' onclick='toggleEditVisitorInfoModal(" . htmlspecialchars(json_encode($appointment)) . ")'>Edit</button> <button class='actionBtn' onclick='confirmDelete(" . $appointment['appointment_id'] . ")'>Delete</button></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td style='text-align: center;' colspan='7'>No appointments found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="editVisitorInfoModal" id="editVisitorInfoModal" onclick="toggleEditVisitorInfoModal()"> 
        <div class="modalContent" onclick="event.stopPropagation()">
            <span class="closeBtn" onclick="toggleEditVisitorInfoModal()">X</span>
            <h2>Edit Visitor Information</h2>
            <form id="editVisitorForm" action="../sql/updateAppointmentInfo.php" method="post">
                <input type="hidden" id="appointment_id" name="appointment_id">

                <label for="editName">Name:</label>
                <input  style="background-color: #a900c7; color: #fff; font-size: 15px;" type="text" id="editName" name="editName" required readonly>

                <label for="editVisitDate">Visit Date:</label>
                <input type="date" id="editVisitDate" name="editVisitDate" required>

                <label for="editCheckIn">Check In Time:</label>
                <select id="editCheckIn" name="editCheckIn" required onchange="updateCheckoutTime()">
                    <option value="" disabled selected>Select Check In Time</option>
                    <option value="7:00 AM">7:00 AM</option>
                    <option value="1:00 PM">1:00 PM</option>
                    <option value="6:00 PM">6:00 PM</option>
                </select>

                <label for="editCheckOut">Check Out Time:</label>
                <select id="editCheckOut" name="editCheckOut" required onchange="toggleCustomCheckoutTime()">
                    <option value="" disabled selected>Select Check Out Time</option>
                    <option value="9:00 AM">9:00 AM</option>
                    <option value="3:00 PM">3:00 PM</option>
                    <option value="8:00 PM">8:00 PM</option>
                    <option value="other">Other (Specify Time)</option>
                </select>

                <input type="text" id="customCheckOutTime" name="customCheckOutTime" placeholder="Enter custom check out time" style="display:none;">

                <label for="editPurpose">Purpose of Visit:</label>
                <textarea id="editPurpose" name="editPurpose"></textarea>

                <label for="editDepartment">Department:</label>
                <select id="editDepartment" name="editDepartment" required>
                    <option value="" disabled selected>Select Department</option>
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

                <label for="editStatus">Visit Status:</label>
                <select id="editStatus" name="editStatus" required>
                    <option value="pending">Pending</option>
                    <option value="checked-in">Checked-in</option>
                    <option value="checked-out">Checked-out</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <label for="editApprovalStatus">Approval Status:</label>
                <select id="editApprovalStatus" name="editApprovalStatus" required>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                </select>

                <input type="hidden" id="encoder" name="encoder" value="<?php echo "Admin " . $_SESSION['name']; ?>">
                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>