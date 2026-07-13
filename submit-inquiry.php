<?php
/**
 * AJAX Submission Handler for Bespoke Design Consultations
 */

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $product_title = isset($_POST['product_title']) ? trim($_POST['product_title']) : 'General Inquiry';
    $whatsapp = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : null;
    
    // Server-side validation
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
        exit;
    }
    
    $db = get_db_connection();
    if ($db) {
        try {
            $stmt = $db->prepare("INSERT INTO `oxo_consultations` (`name`, `email`, `product_title`, `message`, `whatsapp`, `status`) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$name, $email, $product_title, $message, $whatsapp]);
            
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
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
