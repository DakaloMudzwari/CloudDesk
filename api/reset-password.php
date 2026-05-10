<?php

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$token = $input['token'] ?? '';
$newPassword = $input['password'] ?? '';

if (empty($email) || empty($token) || empty($newPassword)) {
    echo json_encode(['error' => 'Email, token, and new password are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $now = date('Y-m-d H:i:s');
        if ($user['otp_code'] === $token && $user['otp_expiration'] > $now) {
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password and clear token
            $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expiration = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Password reset successful'
            ]);
        } else {
            echo json_encode(['error' => 'Invalid or expired reset code']);
        }
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
