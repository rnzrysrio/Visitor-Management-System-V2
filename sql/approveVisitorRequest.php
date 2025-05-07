<?php
include ('db.php');

if (isset($_POST['id'])) {
    $appointmentId = $_POST['id'];
    
    $appointmentId = mysqli_real_escape_string($conn, $appointmentId);

    // Accept request query
    $acceptRequestQuery = "UPDATE visit_request SET approval_status = 'approved' WHERE appointment_id = '$appointmentId'";

    // Execute the query
    if (mysqli_query($conn, $acceptRequestQuery)) {
        console.log("Request approved successfully");
    } else {
        echo "Error approving request: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request.";
}
?>
