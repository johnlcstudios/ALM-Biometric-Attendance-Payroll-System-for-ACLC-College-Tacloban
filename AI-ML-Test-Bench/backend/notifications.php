<?php
// notifications.php - Email notification system using Brevo API
require_once __DIR__ . '/email_config.php';

/**
 * Send email via Brevo API
 */
function sendEmail($to_email, $subject, $html_content)
{
    if (empty(BREVO_API_KEY)) {
        error_log('Brevo API key not configured - email not sent');
        return false;
    }
    
    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $to_email");
        return false;
    }
    
    $data = [
        'sender' => [
            'email' => BREVO_SENDER_EMAIL,
            'name' => BREVO_SENDER_NAME
        ],
        'to' => [['email' => $to_email]],
        'subject' => $subject,
        'htmlContent' => $html_content
    ];
    
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . BREVO_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code === 201) {
        error_log("Email sent successfully to $to_email");
        return true;
    } else {
        error_log("Email failed to $to_email - HTTP $http_code - $curl_error");
        return false;
    }
}

/**
 * Notify employee about leave request status
 */
function notifyLeaveRequest($employee_email, $employee_name, $leave_type, $status, $reason = '')
{
    $subject = "Leave Request $status - ALM Payroll System";
    $html = "<html><body style='font-family: Arial, sans-serif;'>
             <h2 style='color: #1e0178;'>Leave Request Update</h2>
             <p>Dear $employee_name,</p>
             <p>Your leave request (<strong>$leave_type</strong>) has been <strong style='color: " . ($status === 'Approved' ? '#27ae60' : '#db261f') . "'>$status</strong>.</p>";
    
    if ($reason) {
        $html .= "<p><strong>Reason:</strong> $reason</p>";
    }
    
    $html .= "<p style='margin-top: 20px; color: #666;'>Best regards,<br>ALM Payroll System</p>
             </body></html>";
    
    return sendEmail($employee_email, $subject, $html);
}

/**
 * Notify employee about payroll generation
 */
function notifyPayrollGenerated($employee_email, $employee_name, $period, $net_pay)
{
    $subject = "Payroll Generated - $period";
    $html = "<html><body style='font-family: Arial, sans-serif;'>
             <h2 style='color: #1e0178;'>Payroll Notification</h2>
             <p>Dear $employee_name,</p>
             <p>Your payroll for <strong>$period</strong> has been generated.</p>
             <div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                 <p style='font-size: 18px; margin: 0;'><strong>Net Pay: ₱" . number_format($net_pay, 2) . "</strong></p>
             </div>
             <p>Please log in to the system to view your complete payslip.</p>
             <p style='margin-top: 20px; color: #666;'>Best regards,<br>ALM Payroll System</p>
             </body></html>";
    
    return sendEmail($employee_email, $subject, $html);
}

/**
 * Notify employee about loan status
 */
function notifyLoanStatus($employee_email, $employee_name, $status, $amount)
{
    $subject = "Loan Request $status - ALM Payroll System";
    $html = "<html><body style='font-family: Arial, sans-serif;'>
             <h2 style='color: #1e0178;'>Loan Update</h2>
             <p>Dear $employee_name,</p>
             <p>Your loan request of <strong>₱" . number_format($amount, 2) . "</strong> has been <strong style='color: " . ($status === 'Approved' ? '#27ae60' : '#db261f') . "'>$status</strong>.</p>";
    
    if ($status === 'Approved') {
        $html .= "<p>The amount will be reflected in your next payroll.</p>";
    }
    
    $html .= "<p style='margin-top: 20px; color: #666;'>Best regards,<br>ALM Payroll System</p>
             </body></html>";
    
    return sendEmail($employee_email, $subject, $html);
}

/**
 * Notify employee about password reset
 */
function notifyPasswordReset($employee_email, $employee_name, $new_password)
{
    $subject = "Password Reset - ALM Payroll System";
    $html = "<html><body style='font-family: Arial, sans-serif;'>
             <h2 style='color: #1e0178;'>Password Reset</h2>
             <p>Dear $employee_name,</p>
             <p>Your password has been reset by an administrator.</p>
             <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ffc107;'>
                 <p style='margin: 0;'><strong>New Password:</strong> <code style='font-size: 16px;'>$new_password</code></p>
             </div>
             <p style='color: #db261f;'><strong>Important:</strong> Please change your password immediately after logging in.</p>
             <p style='margin-top: 20px; color: #666;'>Best regards,<br>ALM Payroll System</p>
             </body></html>";
    
    return sendEmail($employee_email, $subject, $html);
}
