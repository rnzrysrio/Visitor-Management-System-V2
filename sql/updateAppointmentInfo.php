<?php
session_start();

include ('db.php');

// Check if the necessary form fields are set, and sanitize inputs.
$visit_date = isset($_POST['editVisitDate']) ? mysqli_real_escape_string($conn, $_POST['editVisitDate']) : null;
$checkin_time = isset($_POST['editCheckIn']) ? mysqli_real_escape_string($conn, $_POST['editCheckIn']) : null;
$checkout_time = isset($_POST['editCheckOut']) ? mysqli_real_escape_string($conn, $_POST['editCheckOut']) : null;

$purpose = isset($_POST['editPurpose']) ? mysqli_real_escape_string($conn, $_POST['editPurpose']) : null;
$department = isset($_POST['editDepartment']) ? mysqli_real_escape_string($conn, $_POST['editDepartment']) : null;
$visit_status = isset($_POST['editStatus']) ? mysqli_real_escape_string($conn, $_POST['editStatus']) : null;
$approval_status = isset($_POST['editApprovalStatus']) ? mysqli_real_escape_string($conn, $_POST['editApprovalStatus']) : null;
$encoder = isset($_POST['encoder']) ? mysqli_real_escape_string($conn, $_POST['encoder']) : null;

if ($checkout_time === "other" && !empty($customCheckOutTime)) {
    $checkout_time = $customCheckOutTime;
}

// Sanitize appointment ID
$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;

if ($appointment_id && $visit_date && $checkin_time && $checkout_time && $purpose && $department && $visit_status && $approval_status && $encoder !== null) {
    $updateQuery = "UPDATE all_appointments aa
                    JOIN visit_request vr ON aa.appointment_id = vr.appointment_id
                    SET 
                        aa.visit_date = ?, 
                        aa.check_in_time = ?, 
                        aa.check_out_time = ?, 
                        aa.purpose_of_visit = ?, 
                        aa.go_to_department = ?, 
                        aa.visit_status = ?, 
                        aa.encoder = ?, 
                        vr.approval_status = ?
                    WHERE aa.appointment_id = ?";

    $stmt = mysqli_prepare($conn, $updateQuery);

    if ($stmt) {
        // Bind parameters (s = string, i = integer)
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssi",
            $visit_date,
            $checkin_time,
            $checkout_time,
            $purpose,
            $department,
            $visit_status,
            $encoder,
            $approval_status,
            $appointment_id
        );

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                alert('Record updated successfully!');
                window.location.href = '../pages/adminDashboard.php';
              </script>";
        } else {
            echo "Error executing update: " . mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement: " . mysqli_error($conn);
    }
} else {
    echo "All required fields must be filled.";
}

?>
