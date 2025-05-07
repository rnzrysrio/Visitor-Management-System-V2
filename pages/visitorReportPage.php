<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../index.html?error=notloggedin");
    exit();
}

include('../sql/db.php');

$appointments = [];
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build the base query
$query = "SELECT ua.fullname, ua.email, ua.phone_number, ap_vr.visit_date, ap_vr.check_in_time, ap_vr.check_out_time, ap_vr.purpose_of_visit, ap_vr.go_to_department, ap_vr.visit_status, ap_vr.approval_status, ap_vr.encoder
          FROM user_accounts ua
          INNER JOIN (
              SELECT aa.account_id, aa.visit_date, aa.check_in_time, aa.check_out_time, aa.purpose_of_visit, aa.go_to_department, aa.visit_status, aa.encoder, vr.approval_status 
              FROM all_appointments aa 
              INNER JOIN visit_request vr ON aa.appointment_id = vr.appointment_ID
          ) AS ap_vr
          ON ua.account_id = ap_vr.account_id
          WHERE 1=1";

$params = [];
$types = "";

// Add date range filter if both dates are provided
if (!empty($from) && !empty($to)) {
    $from_date = DateTime::createFromFormat('Y-m-d', $from);
    $to_date = DateTime::createFromFormat('Y-m-d', $to);

    if ($from_date && $to_date && $from_date <= $to_date) {
        $query .= " AND ap_vr.visit_date BETWEEN ? AND ?";
        $params[] = $from;
        $params[] = $to;
        $types .= "ss";
    } else {
        echo "<script>alert('Invalid date range!');</script>";
    }
}

// Add approval status filter if selected
if (!empty($status)) {
    $query .= " AND ap_vr.approval_status = ?";
    $params[] = $status;
    $types .= "s";
}

$stmt = $conn->prepare($query);

if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        echo "<script>alert('Failed to fetch appointment history! Error: " . mysqli_error($conn) . "');</script>";
    }
} else {
    echo "<script>alert('Failed to prepare the query!');</script>";
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
    <link rel="stylesheet" href="../assets/css/visitorReportPage.css">
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

    <div class="appointments">
        <div class="filter-form">
            <form method="GET" action="">
                <label for="from">From:</label>
                <input type="date" id="from" name="from" required>
                <label for="to">To:</label>
                <input type="date" id="to" name="to" required>
                <button type="submit">Filter</button>
                <a href="?" style="margin-left: 10px;">Reset</a>
                <label for="status">Approval Status:</label>
                <select name="status" id="status">
                    <option value="">--Select Status--</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                </select>
            </form>
            <form method="POST" action="../sql/printVisitorReport.php" target="_blank" style="margin-top: 10px;">
                <input type="hidden" name="from" value="<?php echo isset($_GET['from']) ? $_GET['from'] : ''; ?>">
                <input type="hidden" name="to" value="<?php echo isset($_GET['to']) ? $_GET['to'] : ''; ?>">
                <input type="hidden" name="status" value="<?php echo isset($_GET['status']) ? $_GET['status'] : ''; ?>">
                <button id="printer" type="submit">Print PDF</button>
            </form>
        </div>
        <table>
            <caption>Appointment History</caption>
            <thead>
                <tr>
                    <th>Visitor Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Visit Date</th>
                    <th>Check In Time</th>
                    <th>Check Out Time</th>
                    <th>Purpose of Visit</th>
                    <th>Department</th>
                    <th>Visit Status</th>
                    <th>Approval Status</th>
                    <th>Encoder</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($appointments)) {
                    foreach ($appointments as $appointment) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($appointment['fullname']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['phone_number']) . "</td>";
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
                        } else if ($appointment['visit_status'] == 'cancelled') {
                            echo "<td>Cancelled</td>";
                        }
                        else {
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
                        
                        echo "<td>" . htmlspecialchars($appointment['encoder']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr style='text-align: center;'><td colspan='11'>No appointments found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>