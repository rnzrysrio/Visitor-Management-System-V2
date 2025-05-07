<?php
require('../dependencies/fpdf.php');
include('db.php');

$from = isset($_POST['from']) ? $_POST['from'] : '';
$to = isset($_POST['to']) ? $_POST['to'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

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

if (!empty($from) && !empty($to)) {
    $query .= " AND ap_vr.visit_date BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types .= "ss";
}

if (!empty($status)) {
    $query .= " AND ap_vr.approval_status = ?";
    $params[] = $status;
    $types .= "s";
}

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$pdf = new FPDF('L', 'mm', 'Legal');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'All Appointments Report', 0, 1, 'C');

if ($from && $to) {
    $pdf->Cell(0, 10, "From: $from To: $to", 0, 1, 'C');
}
if ($status) {
    $pdf->Cell(0, 10, "Approval Status: " . ucfirst($status), 0, 1, 'C');
}

$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 7);
$headers = ['Visitor Name', 'Email', 'Contact Number', 'Visit Date', 'Check-In', 'Check-Out', 'Purpose of Visit', 'Department', 'Visit Status', 'Approval Status', 'Encoder'];
foreach ($headers as $header) {
    $pdf->Cell(28, 6, $header, 1);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 6.5);
while ($row = $result->fetch_assoc()) {
    $visit_status = $row['visit_status'] == 'checked-in' ? 'Checked-In' : ($row['visit_status'] == 'pending' ? 'Pending' : 'Checked-Out');
    $approval_status = ucfirst($row['approval_status']);

    $pdf->Cell(28, 6, $row['fullname'], 1);
    $pdf->Cell(28, 6, $row['email'], 1);
    $pdf->Cell(28, 6, $row['phone_number'], 1);
    $pdf->Cell(28, 6, $row['visit_date'], 1);
    $pdf->Cell(28, 6, $row['check_in_time'], 1);
    $pdf->Cell(28, 6, $row['check_out_time'], 1);
    $pdf->Cell(28, 6, substr($row['purpose_of_visit'], 0, 20), 1);
    $pdf->Cell(28, 6, $row['go_to_department'], 1);
    $pdf->Cell(28, 6, $visit_status, 1);
    $pdf->Cell(28, 6, $approval_status, 1);
    $pdf->Cell(28, 6, $row['encoder'], 1);
    $pdf->Ln();
}

$pdf->Output();
$stmt->close();
?>
