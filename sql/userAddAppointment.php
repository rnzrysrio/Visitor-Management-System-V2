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
    $customCheckOutTime = mysqli_real_escape_string($conn, $_POST['customCheckOutTime']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $encoder = mysqli_real_escape_string($conn, $_POST['encoder']);

    if ($checkOutTime === "other" && !empty($customCheckOutTime)) {
        $checkOutTime = $customCheckOutTime;
    }

    // Define the valid time slots
    $validTimeSlots = [
        '07:00 AM' => '09:00 AM', // 7 AM to 9 AM
        '01:00 PM' => '03:00 PM', // 1 PM to 3 PM
        '06:00 PM' => '08:00 PM'  // 6 PM to 8 PM
    ];

    // Convert the user's selected check-in and check-out times into DateTime objects
    $checkInTimeObj = DateTime::createFromFormat('h:i A', $checkInTime); // Convert check-in time to DateTime
    $checkOutTimeObj = DateTime::createFromFormat('h:i A', $checkOutTime); // Convert check-out time to DateTime

    // Check if the check-in time falls within any of the valid time slots
    foreach ($validTimeSlots as $validStart => $validEnd) {
        $validStartObj = DateTime::createFromFormat('h:i A', $validStart);
        $validEndObj = DateTime::createFromFormat('h:i A', $validEnd);

        // If the check-in time is within the valid range (check-in time between the start and end time)
        if ($checkInTimeObj >= $validStartObj && $checkInTimeObj <= $validEndObj) {
            // Check if the check-out time is also within the same time range but doesn't exceed the end time
            if ($checkOutTimeObj > $validEndObj) {
                echo "<script>alert('Check-out time cannot be later than the end of the time slot.'); window.location.href='../pages/userDashboard.php';</script>";
                exit();
            }
            // If everything is valid, break out of the loop
            break;
        }
    }


    $visitDateObj = DateTime::createFromFormat('Y-m-d', $visitDate);
    $todayObj = new DateTime();

    // First, check if the appointment is set to a past date
    if ($visitDateObj->format('Y-m-d') < $todayObj->format('Y-m-d')) {
        echo "<script>alert('Appointment date cannot be before today.'); window.location.href='../pages/userDashboard.php';</script>";
        exit();
    }

    // If the appointment is for today, check if the time slot has already passed
    if ($visitDateObj->format('Y-m-d') === $todayObj->format('Y-m-d')) {
        $checkOutTimeObj = DateTime::createFromFormat('h:i A', $checkOutTime);
        $currentTimeOnly = new DateTime();
        $currentTimeOnly->setDate(0, 1, 1);
        $checkOutTimeObj->setDate(0, 1, 1);

        if ($checkOutTimeObj < $currentTimeOnly) {
            echo "<script>alert('This appointment slot has already passed. Please choose a future time slot.'); window.location.href='../pages/userDashboard.php';</script>";
            exit();
        }
    }
    

    // Check if the same appointment is made
    $checkAppointmentDuplication = "SELECT * FROM all_appointments WHERE account_id = ? AND visit_date = ? AND check_in_time = ? AND check_out_time = ?";
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