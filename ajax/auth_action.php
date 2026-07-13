<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$pdo = get_db_connection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection offline.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($data['action'] == 'signup') {
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `oxo_users` WHERE `email` = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already registered.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `oxo_users` (`name`, `email`, `password`) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hash]);

        $user_id = $pdo->lastInsertId();
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user_id;

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($data['action'] == 'login') {
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `oxo_users` WHERE `email` = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
} elseif ($data['action'] == 'google_auth') {
    $credential = $data['credential'] ?? '';
    if (empty($credential)) {
        echo json_encode(['success' => false, 'message' => 'Missing Google credential']);
        exit;
    }

    if ($credential === 'mock_credential_jwt_payload' && defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID') {
        $email = trim($data['email'] ?? 'dev.testuser@example.com');
        $name_part = explode('@', $email)[0];
        $name = ucwords(str_replace(['.', '_', '-'], ' ', $name_part));

        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM `oxo_users` WHERE `email` = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Login
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
            } else {
                // Signup
                $random_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO `oxo_users` (`name`, `email`, `password`) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $random_pass]);

                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $pdo->lastInsertId();
            }

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Decode JWT (header.payload.signature)
    $parts = explode('.', $credential);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
        if ($payload && isset($payload['email'])) {
            $email = $payload['email'];
            $name = $payload['name'] ?? 'Google User';

            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT * FROM `oxo_users` WHERE `email` = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Login
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                } else {
                    // Signup
                    $random_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO `oxo_users` (`name`, `email`, `password`) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $email, $random_pass]);

                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                }

                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Google payload']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Google credential format']);
    }
}
