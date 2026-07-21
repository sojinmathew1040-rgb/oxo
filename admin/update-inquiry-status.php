<?php
/**
 * Secure Backend Endpoint to update lead status from CRM Kanban Board
 */
require_once __DIR__ . '/auth.php';
require_admin_login();

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    
    // Validate status values
    $allowed_statuses = ['Pending', 'Contacted', 'Quoted', 'Addressed'];
    if ($id <= 0 || !in_array($status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
        exit;
    }
    
    $db = get_db_connection();
    if ($db) {
        try {
            $stmt = $db->prepare("UPDATE `oxo_consultations` SET `status` = ? WHERE `id` = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true]);
            exit;
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Database connection offline.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}
