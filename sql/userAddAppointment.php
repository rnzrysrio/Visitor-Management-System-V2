<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('db.php');  // Database connection

    date_default_timezone_set('Asia/Manila');
    $appointmentRequestDate = date('Y-m-d');

    if (isset($_SESSION['account_id'])) {
        $accountId = $_SESSION['account_id'];
    } else {
        die("Error: No account ID found in session. Please log in again.");
    }

    $visitDate = mysqli_real_escape_string($conn, $_POST['visit-date']);
    $checkInTime = mysqli_real_escape_string($conn, $_POST['checkin']);
    $checkOutTime = mysqli_real_escape_string($conn, $_POST['checkout']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $encoder = mysqli_real_escape_string($conn, $_POST['encoder']);


    // Check if the same appointment is made
    $checkAppointmentDuplication = "SELECT aa.account_id, aa.visit_date, aa.check_in_time, aa.check_out_time FROM all_appointments aa INNER JOIN user_accounts ua ON ua.account_id = aa.account_id WHERE aa.account_id = ? AND aa.visit_date = ? AND aa.check_in_time = ? AND aa.check_out_time = ?";
    $stmt = $conn->prepare($checkAppointmentDuplication);
    $stmt->bind_param("ssss", $accountId, $visitDate, $checkInTime, $checkOutTime);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
        alert('You already made an appointment with the same date, check-in time, and check-out time!');
        window.location.href = '../pages/userDashboard.php';
        </script>";
    } else {
        // Insert appointment with registration date right after phone
        $appointmentQuery = "INSERT INTO all_appointments (account_id, visit_date, check_in_time, check_out_time, purpose_of_visit, go_to_department, encoder) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($appointmentQuery);
        $stmt->bind_param("sssssss", $accountId, $visitDate, $checkInTime, $checkOutTime, $purpose, $department, $encoder);

        if ($stmt->execute()) {
            $appointmentId = $conn->insert_id;
            echo "<script>alert('Appointment Request Saved!');</script>";

            $visitRequestQuery = "INSERT INTO visit_request (appointment_id, request_creation_date) VALUES (?, ?)";
            $stmt2 = $conn->prepare($visitRequestQuery);
            $stmt2->bind_param("ss", $appointmentId, $appointmentRequestDate);

            if($stmt2->execute()) {
                echo "<script>
                alert('Visit Request Sent!');
                window.location.href = '../pages/userDashboard.php';
                </script>";
            } else {
                echo "<script>alert('Request Failed!');</script>";
            }
            $stmt2->close();
        } else {
            echo "<script>alert('Rqquest Failed!');</script>";
        }
    }
    $stmt->close();
    $conn->close();
}
?>