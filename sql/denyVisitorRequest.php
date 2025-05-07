<?php
include ('db.php');

if (isset($_POST['id'])) {
    $appointmentId = $_POST['id'];
    
    $appointmentId = mysqli_real_escape_string($conn, $appointmentId);

    // Deny request query
    $denyRequestQuery = "UPDATE visit_request SET approval_status = 'denied' WHERE appointment_id = '$appointmentId'";

    // Execute the query
    if (mysqli_query($conn, $denyRequestQuery)) {
        console.log("Request denied successfully");
    } else {
        echo "Error denying request: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request.";
}
?>
