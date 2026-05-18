<?php
// Email notification system using Brevo (free API - 300 emails/day)

function sendEmailNotification($to, $subject, $htmlBody) {
    $apiKey = getenv('BREVO_API_KEY');
    if (!$apiKey) {
        error_log("Brevo API key not configured");
        return false;
    }
    
    $fromEmail = getenv('FROM_EMAIL') ?: 'noreply@alm-biometrics.com';
    
    $data = [
        'sender' => ['email' => $fromEmail, 'name' => 'ALM Biometrics'],
        'to' => [['email' => $to]],
        'subject' => $subject,
        'htmlContent' => $htmlBody
    ];
    
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        return true;
    } else {
        error_log("Email send failed: HTTP $httpCode - $response");
        return false;
    }
}

function notifyLeaveRequest($employeeEmail, $employeeName, $leaveType, $status) {
    $subject = "Leave Request $status";
    $htmlBody = "
        <h2>Leave Request Update</h2>
        <p>Dear $employeeName,</p>
        <p>Your leave request ($leaveType) has been <strong>$status</strong>.</p>
        <p>Please contact HR if you have any questions.</p>
        <br>
        <p><small>This is an automated message from ALM Biometrics System</small></p>
    ";
    return sendEmailNotification($employeeEmail, $subject, $htmlBody);
}

function notifyPayrollGenerated($employeeEmail, $employeeName, $period, $netPay) {
    $subject = "Payroll Generated - $period";
    $htmlBody = "
        <h2>Payroll Notification</h2>
        <p>Dear $employeeName,</p>
        <p>Your payroll for period <strong>$period</strong> has been generated.</p>
        <p><strong>Net Pay: ₱" . number_format($netPay, 2) . "</strong></p>
        <p>Please check the system for detailed breakdown.</p>
        <br>
        <p><small>This is an automated message from ALM Biometrics System</small></p>
    ";
    return sendEmailNotification($employeeEmail, $subject, $htmlBody);
}

function notifyLoanStatus($employeeEmail, $employeeName, $amount, $status) {
    $subject = "Cash Advance Request $status";
    $htmlBody = "
        <h2>Cash Advance Request Update</h2>
        <p>Dear $employeeName,</p>
        <p>Your cash advance request of <strong>₱" . number_format($amount, 2) . "</strong> has been <strong>$status</strong>.</p>
        <p>Please contact HR/Payroll for more details.</p>
        <br>
        <p><small>This is an automated message from ALM Biometrics System</small></p>
    ";
    return sendEmailNotification($employeeEmail, $subject, $htmlBody);
}
?>
