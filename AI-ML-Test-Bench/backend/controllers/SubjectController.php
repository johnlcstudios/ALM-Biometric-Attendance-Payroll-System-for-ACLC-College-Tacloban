<?php
// controllers/SubjectController.php

function handle_get_subjects($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE company_id = ? ORDER BY code ASC");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_save_subject($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['code']) || empty($data['description'])) {
        echo json_encode(['success' => false, 'message' => 'Code and description are required']);
        return;
    }
    try {
        if (isset($data['id']) && !empty($data['id'])) {
            $pdo->prepare("UPDATE subjects SET code=?, description=?, units=?, hours=? WHERE id=? AND company_id=?")->execute([$data['code'], $data['description'], $data['units'], $data['hours'], $data['id'], $_SESSION['company_id']]);
        } else {
            $pdo->prepare("INSERT INTO subjects (company_id, code, description, units, hours) VALUES (?, ?, ?, ?, ?)")->execute([$_SESSION['company_id'], $data['code'], $data['description'], $data['units'], $data['hours']]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("save_subject error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_delete_subject($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id = $_GET['id'] ?? '';
    $pdo->prepare("DELETE FROM subjects WHERE id = ? AND company_id = ?")->execute([$id, $_SESSION['company_id']]);
    echo json_encode(['success' => true]);
}

function handle_get_subject_loads($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT sl.*, e.full_name as faculty_name FROM subject_loads sl JOIN employees e ON sl.faculty_id = e.id WHERE sl.company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_save_subject_load($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        $pdo->prepare("INSERT INTO subject_loads (company_id, faculty_id, code, description, units, hours) VALUES (?, ?, ?, ?, ?, ?)")->execute([$_SESSION['company_id'], $data['faculty_id'], $data['code'], $data['description'], $data['units'], $data['hours']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("save_subject_load error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_delete_subject_load($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id = $_GET['id'] ?? '';
    $pdo->prepare("DELETE FROM subject_loads WHERE id = ? AND company_id = ?")->execute([$id, $_SESSION['company_id']]);
    echo json_encode(['success' => true]);
}
