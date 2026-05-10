<?php

require_once 'config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $ticketId = $_GET['ticket_id'] ?? '';
        if (empty($ticketId)) {
            echo json_encode(['error' => 'Ticket ID is required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT c.*, u.name as user_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.ticket_id = ? ORDER BY c.created_at ASC");
            $stmt->execute([$ticketId]);
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to fetch comments: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $ticketId = $input['ticket_id'] ?? '';
        $userId = $input['user_id'] ?? '';
        $content = $input['content'] ?? '';

        if (empty($ticketId) || empty($userId) || empty($content)) {
            echo json_encode(['error' => 'Ticket ID, User ID, and Content are required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO comments (ticket_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$ticketId, $userId, $content]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to add comment: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
