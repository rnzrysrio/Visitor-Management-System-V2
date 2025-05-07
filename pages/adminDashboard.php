<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: loginPage.php");
    exit();
}
 include('../sql/db.php');
// include('dbQueries/autoStatusManager.php');

//Asian Timezone
date_default_timezone_set('Asia/Manila');
$today = date("Y-m-d");
$currentTime = date("H:i:s");

// Total Visitors Count (excluding rejected and pending appointments)
$sql_total = "SELECT COUNT(*) FROM all_appointments aa INNER JOIN visit_request vr ON aa.appointment_id = vr.appointment_id WHERE vr.approval_status = 'approved'";
$result_total = $conn->query($sql_total);
$row_total = $result_total->fetch_assoc();
$total_visitors = $row_total['COUNT(*)'];

// New Visitors Today Count (excluding rejected and pending appointments)
$sql_new_today = "SELECT COUNT(*) FROM all_appointments aa INNER JOIN visit_request vr ON aa.appointment_id = vr.appointment_id WHERE vr.approval_status = 'approved' AND aa.visit_date = '$today'";
$result_new_today = $conn->query($sql_new_today);
$row_new_today = $result_new_today->fetch_assoc();
$new_visitors_today = $row_new_today['COUNT(*)'];

// Checked In Visitors Count (excluding rejected and pending appointments)
$sql_checked_in = "SELECT COUNT(*) FROM all_appointments aa INNER JOIN visit_request vr ON aa.appointment_id = vr.appointment_id WHERE vr.approval_status = 'approved' AND aa.visit_date = '$today' AND aa.visit_status = 'checked_in'";
$result_checked_in = $conn->query($sql_checked_in);
$row_checked_in = $result_checked_in->fetch_assoc();
$checked_in_visitors = $row_checked_in['COUNT(*)'];

// Checked Out Visitors Count (excluding rejected and pending appointments)
$sql_checked_out = "SELECT COUNT(*) FROM all_appointments aa INNER JOIN visit_request vr ON aa.appointment_id = vr.appointment_id WHERE vr.approval_status = 'approved' AND aa.visit_date = '$today' AND aa.visit_status = 'checked_out'";
$result_checked_out = $conn->query($sql_checked_out);
$row_checked_out = $result_checked_out->fetch_assoc();
$checked_out_visitors = $row_checked_out['COUNT(*)'];

// Accepted Appointments Count (not rejected, not expired)
$sql_accepted = "SELECT COUNT(*) FROM visit_request WHERE approval_status = 'approved'";
$result_accepted = $conn->query($sql_accepted);
$accepted_appointments = $result_accepted->fetch_assoc()['COUNT(*)'];

// Rejected Appointments Count
$sql_rejected = "SELECT COUNT(*) FROM visit_request WHERE approval_status = 'denied'";
$result_rejected = $conn->query($sql_rejected);
$rejected_appointments = $result_rejected->fetch_assoc()['COUNT(*)'];

// Pending Appointments Count (still waiting, exclude expired ones)
$sql_pending = "SELECT COUNT(*) FROM all_appointments aa INNER JOIN visit_request vr 
                ON aa.appointment_id = vr.appointment_id
                WHERE vr.approval_status = 'pending' AND (
                        aa.visit_date > '$today' OR 
                        (aa.visit_date = '$today' AND STR_TO_DATE(aa.check_in_time, '%h:%i %p') > STR_TO_DATE('$currentTime', '%H:%i:%s'))
                )";
$result_pending = $conn->query($sql_pending);
$pending_appointments = $result_pending->fetch_assoc()['COUNT(*)'];

// Reserved Visitors Count
$sql_reserved = "SELECT COUNT(*) FROM all_appointments aa INNER JOIN visit_request vr 
                 ON aa.appointment_id = vr.appointment_id 
                 WHERE vr.approval_status = 'approved' AND (
                        aa.visit_date > '$today' OR 
                        (aa.visit_date = '$today' AND STR_TO_DATE(aa.check_in_time, '%h:%i %p') > '$currentTime')) 
                   AND aa.visit_status = 'pending'";
$result_reserved = $conn->query($sql_reserved);
$row_reserved = $result_reserved->fetch_assoc();
$reserved_visitors = $row_reserved['COUNT(*)'];
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
    <link rel="stylesheet" href="../assets/css/adminDashboard.css">
    <script src="../automation_scripts/script.js"></script>
    <title>VMS</title>
</head>
<body>
    <div class="userHeader">
        <h1 id="userGreeting">Admin: <?php echo $_SESSION['name']?></h1>
        <ul>
            <li>
                <a href="visitorRequestsPage.php">Visitor Requests</a>
                <a href="manageVisitorPage.php">Manage Visitors</a>
                <a href="visitorReportPage.php">Visitor Report</a>
            </li>
        </ul>
        <div class="profile-dropdown">
            <button id="dropbtn" class="dropbtn" onclick="toggleProfile()">&#9662;</button>
            <div id="dropdown-content" class="dropdown-content">
                <a href="../sql/accountLogout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="dashboard">
        <h1>Dashboard</h1>
        <hr>
        <div class="card-container">
            <div class="card">
                <h2>Total Visitors</h2>
                <p><?php echo $total_visitors; ?></p>
            </div>
            <div class="card">
                <h2>New Visitors Today</h2>
                <p><?php echo $new_visitors_today; ?></p>
            </div>
            <div class="card">
                <h2>Visitors Checked In</h2>
                <p><?php echo $checked_in_visitors; ?></p>
            </div>
            <div class="card">
                <h2>Visitors Checked Out</h2>
                <p><?php echo $checked_out_visitors; ?></p>
            </div>
            <div class="card">
                <h2>Scheduled Visitors</h2>
                <p><?php echo $reserved_visitors; ?></p>
            </div>
            <div class="card">
                <h2>Accepted Appointments</h2>
                <p><?php echo $accepted_appointments; ?></p>
            </div>
            <div class="card">
                <h2>Rejected Appointments</h2>
                <p><?php echo $rejected_appointments; ?></p>
            </div>
            <div class="card">
                <h2>Pending Appointments</h2>
                <p><?php echo $pending_appointments; ?></p>
            </div>
        </div>
    </div>
</body>
</html>