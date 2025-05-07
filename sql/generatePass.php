<?php
require('../dependencies/fpdf.php');
include('db.php');

$appointment_id = isset($_POST['appointment_id']) ? $_POST['appointment_id'] : '';
$appointment_id = mysqli_real_escape_string($conn, $appointment_id);

$stmt = $conn->prepare("SELECT aa.appointment_id, ua.fullname, aa.visit_date, aa.check_in_time, aa.check_out_time, aa.go_to_department, vr.approval_status
                        FROM user_accounts ua
                        JOIN all_appointments aa ON ua.account_id = aa.account_id
                        JOIN visit_request vr ON aa.appointment_id = vr.appointment_ID
                        WHERE aa.appointment_id = ? AND vr.approval_status = 'approved'");
$stmt->bind_param("s", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Define ID card size (CR80 in mm): 85.6 x 54 mm (landscape)
    $pdf = new FPDF('L', 'mm', [85.6, 54]);
    $pdf->AddPage();

    $pdf->SetFillColor(240, 248, 255);
    $pdf->Rect(0, 0, 85.6, 54, 'F');

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Rect(1, 1, 83.6, 52);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(0, 1, 'Visitor Pass', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, 'Appointment ID: ' . $row['appointment_id'], 0, 1, 'C');

    $pdf->SetFont('Arial', '', 6); // Even smaller font size

    $pdf->Cell(30, 3, 'Name:', 0, 0);
    $pdf->Cell(55, 3, $row['fullname'], 0, 1);

    $pdf->Cell(30, 2.5, 'Visit Date:', 0, 0);
    $pdf->Cell(55, 2.5, $row['visit_date'], 0, 1);

    $pdf->Cell(30, 2.5, 'Check-In:', 0, 0);
    $pdf->Cell(55, 2.5, $row['check_in_time'], 0, 1);

    $pdf->Cell(30, 2.5, 'Check-Out:', 0, 0);
    $pdf->Cell(55, 2.5, $row['check_out_time'], 0, 1);

    $pdf->Cell(30, 2.5, 'Department:', 0, 0);
    $pdf->Cell(55, 2.5, $row['go_to_department'], 0, 1);

    $pdf->Cell(30, 3, 'Status:', 0, 0);
    $pdf->Cell(55, 3, ucfirst($row['approval_status']), 0, 1);

    $pdf->Output('I', 'visitor_pass_' . $appointment_id . '.pdf');
} else {
    echo "Visitor pass not found or not approved.";
}
?>
