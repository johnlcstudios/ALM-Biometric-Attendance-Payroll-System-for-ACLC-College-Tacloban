<?php
// email_config.php - Email configuration for Brevo API
require_once __DIR__ . '/db.php';

define('BREVO_API_KEY', $_ENV['BREVO_API_KEY'] ?? '');
define('BREVO_SENDER_EMAIL', $_ENV['BREVO_SENDER_EMAIL'] ?? 'noreply@yourcompany.com');
define('BREVO_SENDER_NAME', $_ENV['BREVO_SENDER_NAME'] ?? 'ALM Payroll System');
