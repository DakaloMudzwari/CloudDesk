<?php

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$firstName = $input['firstName'] ?? '';
$lastName = $input['lastName'] ?? '';
$email = $input['email'] ?? '';
$department = $input['department'] ?? '';
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'user';

if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    echo json_encode(['error' => 'All fields except department are required']);
    exit;
}

$name = $firstName . ' ' . $lastName;
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Insert user with pending status
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, department, role, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$name, $email, $hashedPassword, $department, $role]);

    echo json_encode(['success' => true, 'message' => 'Registration successful! Your account is pending admin approval.']);
} catch (Exception $e) {
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
