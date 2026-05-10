<?php

require_once 'config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];


function getCurrentUserId()
{
    return $_GET['user_id'] ?? null;
}

switch ($method) {
    case 'GET':
        $userId = getCurrentUserId();
        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT t.*, u.name as requester_name FROM tickets t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
        }
        
        $tickets = $stmt->fetchAll();
        $now = time();

        foreach ($tickets as &$ticket) {
            if ($ticket['resolved_at']) {
                continue;
            }

            if (empty($ticket['sla_resolution_due'])) {
                continue;
            }

            $resolutionDue = strtotime($ticket['sla_resolution_due']);
            $createdAt = strtotime($ticket['created_at']);
            $totalAllowed = $resolutionDue - $createdAt;
            $elapsed = $now - $createdAt;

            if ($now > $resolutionDue) {
                $ticket['sla_status'] = 'breached';
            } elseif ($elapsed > ($totalAllowed * 0.75)) {
                $ticket['sla_status'] = 'warning';
            } else {
                $ticket['sla_status'] = 'on-track';
            }
        }
        
        echo json_encode($tickets);
        break;

    case 'POST':
        // Handle both JSON and FormData
        if (isset($_POST['userId'])) {
            $input = $_POST;
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
        }

        $id = 'TKT-' . rand(1000, 9999);
        $userId = $input['userId'] ?? null;
        $title = $input['title'] ?? '';
        $category = $input['category'] ?? '';
        $priority = $input['priority'] ?? '';
        $description = $input['description'] ?? '';
        
        $attachmentPath = null;
        
        // Handle File Upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $attachmentPath = 'uploads/' . $fileName; // Store relative path
            }
        }

        try {
            // Fetch SLA config
            $stmt = $pdo->prepare("SELECT * FROM slaconfigs WHERE priority = ?");
            $stmt->execute([$priority]);
            $sla = $stmt->fetch();

            $firstResponseMinutes = $sla ? $sla['first_response_minutes'] : 480; // default medium
            $resolutionMinutes = $sla ? $sla['resolution_minutes'] : 1440; // default medium

            $createdAt = date('Y-m-d H:i:s');
            $firstResponseDue = date('Y-m-d H:i:s', strtotime("+{$firstResponseMinutes} minutes"));
            $resolutionDue = date('Y-m-d H:i:s', strtotime("+{$resolutionMinutes} minutes"));

            $stmt = $pdo->prepare("INSERT INTO tickets (id, user_id, title, category, priority, description, sla_first_response_due, sla_resolution_due, created_at, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $userId, $title, $category, $priority, $description, $firstResponseDue, $resolutionDue, $createdAt, $attachmentPath]);

            echo json_encode(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';
        $status = $input['status'] ?? '';
        $priority = $input['priority'] ?? '';
        $assigneeName = $input['assignee'] ?? '';

        if (empty($id)) {
            echo json_encode(['error' => 'Ticket ID is required']);
            exit;
        }

        try {
            $assigneeId = null;
            if (!empty($assigneeName)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE name = ?");
                $stmt->execute([$assigneeName]);
                $user = $stmt->fetch();
                if ($user) {
                    $assigneeId = $user['id'];
                }
            }

            // Fetch current ticket
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
            $stmt->execute([$id]);
            $currentTicket = $stmt->fetch();

            $firstResponseAt = $currentTicket['first_response_at'];
            $resolvedAt = $currentTicket['resolved_at'];
            $slaStatus = $currentTicket['sla_status'];

            if ($status === 'progress' && empty($firstResponseAt)) {
                $firstResponseAt = date('Y-m-d H:i:s');
            }

            if ($status === 'resolved' && empty($resolvedAt)) {
                $resolvedAt = date('Y-m-d H:i:s');
                // Calculate final SLA status
                $resolutionDue = strtotime($currentTicket['sla_resolution_due']);
                $resolvedTime = strtotime($resolvedAt);
                if ($resolvedTime > $resolutionDue) {
                    $slaStatus = 'breached';
                } else {
                    $slaStatus = 'on-track';
                }
            }

            $stmt = $pdo->prepare("UPDATE tickets SET status = ?, priority = ?, assignee_id = ?, first_response_at = ?, resolved_at = ?, sla_status = ? WHERE id = ?");
            $stmt->execute([$status, $priority, $assigneeId, $firstResponseAt, $resolvedAt, $slaStatus, $id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to update ticket: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
