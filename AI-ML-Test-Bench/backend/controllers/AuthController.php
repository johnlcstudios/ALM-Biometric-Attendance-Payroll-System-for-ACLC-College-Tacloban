<?php
// controllers/AuthController.php

function handle_login($pdo) {
    checkRateLimit('login');
    $data     = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        return;
    }

    $stmt = $pdo->prepare("SELECT u.*, c.name as company_name, c.timezone as company_timezone, e.full_name as emp_full_name FROM users u JOIN companies c ON u.company_id = c.id LEFT JOIN employees e ON u.id = e.user_id WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']          = $user['id'];
        $_SESSION['company_id']       = $user['company_id'];
        $_SESSION['role']             = trim($user['role']);
        $_SESSION['company_name']     = $user['company_name'];
        $_SESSION['company_timezone'] = $user['company_timezone'] ?: 'Asia/Manila';
        $_SESSION['username']         = $user['username'];
        $_SESSION['full_name']        = $user['emp_full_name'] ?: $user['username'];
        date_default_timezone_set($_SESSION['company_timezone']);
        echo json_encode(['success' => true, 'role' => trim($user['role']), 'company_name' => $user['company_name']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
}

function handle_signup($pdo) {
    $data         = json_decode(file_get_contents('php://input'), true);
    $company_name = $data['company_name'] ?? '';
    $username     = $data['username']     ?? '';
    $email        = $data['email']        ?? '';
    $password     = $data['password']     ?? '';

    if (empty($company_name) || empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO companies (name, admin_email) VALUES (?, ?)");
        $stmt->execute([$company_name, $email]);
        $company_id = $pdo->lastInsertId();

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, 'HR', ?)");
        $stmt->execute([$company_id, $username, $hashed, $email]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("signup error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
}

function handle_logout() {
    session_destroy();
    echo json_encode(['success' => true]);
}

function handle_change_password($pdo) {
    $data    = json_decode(file_get_contents('php://input'), true);
    $oldPass = $data['oldPass'] ?? '';
    $newPass = $data['newPass'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user && password_verify($oldPass, $user['password'])) {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect current password']);
    }
}

function handle_reset_password($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $target_user_id = $_GET['user_id'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND company_id = ?");
    $stmt->execute([$target_user_id, $_SESSION['company_id']]);
    if (!$stmt->fetch()) sendError(404, 'User not found or access denied');

    $new_pass = password_hash('welcome123', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND company_id = ?")->execute([$new_pass, $target_user_id, $_SESSION['company_id']]);
    echo json_encode(['success' => true, 'message' => 'Password reset to welcome123']);
}
