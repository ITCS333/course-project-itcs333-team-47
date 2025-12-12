<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents('php://input'), true);

$resource = $_GET['resource'] ?? 'weeks';

function getAllWeeks($db) {
    $search = $_GET['search'] ?? null;
    $sort = $_GET['sort'] ?? 'start_date';
    $order = $_GET['order'] ?? 'asc';


    $query = "SELECT id, title, start_date, description, links, created_at FROM weeks";


    $params = [];
    if ($search) {
        $query .= " WHERE title LIKE ? OR description LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }


    $allowedSortFields = ['title', 'start_date', 'created_at'];
    if (!in_array($sort, $allowedSortFields)) {
        $sort = 'start_date';
    }


    if (!in_array($order, ['asc', 'desc'])) {
        $order = 'asc';
    }


    $query .= " ORDER BY $sort $order";


    $stmt = $db->prepare($query);


    $stmt->execute($params);


    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($weeks as &$week) {
        $week['links'] = json_decode($week['links'], true);
    }


    sendResponse(['success' => true, 'data' => $weeks]);
}

function getWeekById($db, $weekId) {
    if (!$weekId) {
        sendError('week_id is required', 400);
    }

    $stmt = $db->prepare("SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id = ?");
    $stmt->execute([$weekId]);
    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($week) {
        $week['links'] = json_decode($week['links'], true);
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        sendError('Week not found', 404);
    }
}

function createWeek($db, $data) {
    $required = ['title', 'start_date', 'description'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            sendError("$field is required", 400);
        }
    }
    
    $title = sanitizeInput($data['title']);
    $startDate = $data['start_date'];
    $description = sanitizeInput($data['description']);
    
    if (!validateDate($startDate)) {
        sendError('Invalid start_date format. Use YYYY-MM-DD', 400);
    }
    
    $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);
    
    $stmt = $db->prepare("INSERT INTO weeks (title, start_date, description, links) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$title, $startDate, $description, $links])) {
        $newWeek = [
            'id' => $db->lastInsertId(),
            'title' => $title,
            'start_date' => $startDate,
            'description' => $description,
            'links' => json_decode($links, true)
        ];
        sendResponse(['success' => true, 'data' => $newWeek], 201);
    } else {
        sendError('Failed to create week', 500);
    }
}

function updateWeek($db, $data) {
    $weekId = $data['id'] ?? null;
    if (!$weekId) {
        sendError('id is required', 400);
    }
    
    $stmt = $db->prepare("SELECT * FROM weeks WHERE id = ?");
    $stmt->execute([$weekId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendError('Week not found', 404);
    }


    $setClauses = [];
    $params = [];


    if (isset($data['title'])) {
        $setClauses[] = "title = ?";
        $params[] = sanitizeInput($data['title']);
    }
    if (isset($data['start_date'])) {
        if (!validateDate($data['start_date'])) {
            sendError('Invalid start_date format', 400);
        }
        $setClauses[] = "start_date = ?";
        $params[] = $data['start_date'];
    }
    if (isset($data['description'])) {
        $setClauses[] = "description = ?";
        $params[] = sanitizeInput($data['description']);
    }
    if (isset($data['links'])) {
        $setClauses[] = "links = ?";
        $params[] = json_encode($data['links']);
    }


    if (empty($setClauses)) {
        sendError('No fields to update', 400);
    }


    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    
    $query = "UPDATE weeks SET " . implode(', ', $setClauses) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $params[] = $weekId;
    
    if ($stmt->execute($params)) {
        $stmt = $db->prepare("SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id = ?");
        $stmt->execute([$weekId]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        $updated['links'] = json_decode($updated['links'], true);
        sendResponse(['success' => true, 'data' => $updated]);
    } else {
        sendError('Failed to update week', 500);
    }
}

function deleteWeek($db, $weekId) {
    if (!$weekId) {
        sendError('week_id is required', 400);
    }
    
    $stmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $stmt->execute([$weekId]);
    if (!$stmt->fetch()) {
        sendError('Week not found', 404);
    }
    
    $stmt = $db->prepare("DELETE FROM comments_week WHERE week_id = ?");
    $stmt->execute([$weekId]);
    
    $stmt = $db->prepare("DELETE FROM weeks WHERE id = ?");
    
    if ($stmt->execute([$weekId])) {
        sendResponse(['success' => true, 'message' => 'Week and associated comments deleted']);
    } else {
        sendError('Failed to delete week', 500);
    }
}

function getCommentsByWeek($db, $weekId) {
    if (!$weekId) {
        sendError('week_id is required', 400);
    }
    
    $stmt = $db->prepare("SELECT id, week_id, author, text, created_at FROM comments_week WHERE week_id = ? ORDER BY created_at ASC");
    $stmt->execute([$weekId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $comments]);
}

function createComment($db, $data) {
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            sendError("$field is required", 400);
        }
    }

    $weekId = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    if (empty($text)) {
        sendError('Comment text cannot be empty', 400);
    }
    
    $stmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $stmt->execute([$weekId]);
    if (!$stmt->fetch()) {
        sendError('Week not found', 404);
    }
    
    $stmt = $db->prepare("INSERT INTO comments_week (week_id, author, text) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$weekId, $author, $text])) {
        $newComment = [
            'id' => $db->lastInsertId(),
            'week_id' => $weekId,
            'author' => $author,
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s')
        ];
        sendResponse(['success' => true, 'data' => $newComment], 201);
    } else {
        sendError('Failed to create comment', 500);
    }
}

function deleteComment($db, $commentId) {
    if (!$commentId) {
        sendError('comment_id is required', 400);
    }
    
    $stmt = $db->prepare("SELECT id FROM comments_week WHERE id = ?");
    $stmt->execute([$commentId]);
    if (!$stmt->fetch()) {
        sendError('Comment not found', 404);
    }
    
    $stmt = $db->prepare("DELETE FROM comments_week WHERE id = ?");
    
    if ($stmt->execute([$commentId])) {
        sendResponse(['success' => true, 'message' => 'Comment deleted']);
    } else {
        sendError('Failed to delete comment', 500);
    }
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit();
}

function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

try {
    if ($resource === 'weeks') {
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    getWeekById($db, $_GET['id']);
                } else {
                    getAllWeeks($db);
                }
                break;
            
            case 'POST':
                createWeek($db, $input);
                break;
            
            case 'PUT':
                updateWeek($db, $input);
                break;
            
            case 'DELETE':
                $weekId = $input['id'] ?? $_GET['id'] ?? null;
                deleteWeek($db, $weekId);
                break;
            
            default:
                sendError('Method not allowed', 405);
        }
    } elseif ($resource === 'comments') {
        switch ($method) {
            case 'GET':
                if (isset($_GET['week_id'])) {
                    getCommentsByWeek($db, $_GET['week_id']);
                } else {
                    sendError('week_id is required', 400);
                }
                break;
            
            case 'POST':
                createComment($db, $input);
                break;
            
            case 'DELETE':
                $commentId = $input['id'] ?? $_GET['id'] ?? null;
                deleteComment($db, $commentId);
                break;
            
            default:
                sendError('Method not allowed', 405);
        }
    } else {
        sendError('Invalid resource', 400);
    }
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
