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
    
    $stmt = $conn->prepare("SELECT ua.fullname, ap.appointment_id, ap.visit_date, ap.check_in_time, ap.check_out_time, ap.go_to_department, ap.visit_status, vr.approval_status FROM user_accounts ua JOIN all_appointments ap ON ua.account_id = ap.account_id JOIN visit_request vr ON ap.appointment_id = vr.appointment_id WHERE ua.fullname LIKE ? AND vr.approval_status = 'pending';");
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
    $stmt2 = $conn->prepare("SELECT ua.fullname, ap.appointment_id, ap.visit_date, ap.check_in_time, ap.check_out_time, ap.go_to_department, ap.visit_status, vr.approval_status FROM user_accounts ua JOIN all_appointments ap ON ua.account_id = ap.account_id JOIN visit_request vr ON ap.appointment_id = vr.appointment_id WHERE vr.approval_status = 'pending';");
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
                <caption>Pending Requests</caption>
                <thead>
                    <tr>
                        <th>Visitor Name</th>
                        <th>Visit Date</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                        <th>Department</th>
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
                            if ($appointment['go_to_department'] == 'HR1') {
                                echo "<td>Human Resource Department 1</td>";
                            } else if ($appointment['go_to_department'] == 'HR2') {
                                echo "<td>Human Resource Department 2</td>";
                            } else if ($appointment['go_to_department'] == 'LGSTCS1') {
                                echo "<td>Logistics Department 1</td>";
                            } else if ($appointment['go_to_department'] == 'LGSTCS2') {
                                echo "<td>Logistics Department 2</td>";
                            } else if ($appointment['go_to_department'] == 'CORE1') {
                                echo "<td>Core Transaction Department 1</td>";
                            } else if ($appointment['go_to_department'] == 'CORE2') {
                                echo "<td>Core Transaction Department 2</td>";
                            } else if ($appointment['go_to_department'] == 'ADMIN') {
                                echo "<td>Administrative Department</td>";
                            } else if ($appointment['go_to_department'] == 'IT') {
                                echo "<td>Information Technology Department</td>";
                            } else if ($appointment['go_to_department'] == 'FINANCE') {
                                echo "<td>Financial Department</td>";
                            } else {
                                echo "<td>Unknown</td>";
                            }
                        if ($appointment['visit_status'] == 'checked_in') {
                            echo "<td>Checked-In</td>";
                        } else if ($appointment['visit_status'] == 'checked_out') {
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

                            echo "<td id='actionCell'><button class='actionBtn' onclick='approveRequest(" . $appointment['appointment_id'] . ")'>Approve</button> <button class='actionBtn' onclick='denyRequest(" . $appointment['appointment_id'] . ")'>Deny</button></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td style='text-align: center;' colspan='8'>No recent requests found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>