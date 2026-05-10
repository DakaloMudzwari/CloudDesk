<?php

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (empty($email)) {
    echo json_encode(['error' => 'Email is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = sprintf("%06d", mt_rand(1, 999999));
        $expiration = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiration = ? WHERE id = ?");
        $stmt->execute([$token, $expiration, $user['id']]);

        // Mock sending email by returning it in response
        echo json_encode([
            'success' => true,
            'message' => 'Reset code generated',
            'token' => $token 
        ]);
    } else {
        // Standard security practice: don't reveal if email exists
        echo json_encode([
            'success' => true,
            'message' => 'If that email exists, a code has been generated.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
