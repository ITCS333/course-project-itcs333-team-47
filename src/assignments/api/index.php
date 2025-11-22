<?php
/**
 * Assignment Management API
 * 
 * RESTful API for assignments + comments using PDO + MySQL
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// Set Content-Type header to application/json
header('Content-Type: application/json; charset=utf-8');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// Include the database connection class
require_once __DIR__ . '/Database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Set PDO to throw exceptions on errors
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ============================================================================
// REQUEST PARSING
// ============================================================================

// Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Get the request body for POST and PUT requests
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = [];
}

// Parse query parameters
$queryParams = $_GET;


// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Get all assignments
 */
function getAllAssignments(PDO $db)
{
    $sql = "SELECT * FROM assignments WHERE 1=1";
    $params = [];

    // search filter
    if (!empty($_GET['search'])) {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    // sorting
    $allowedSort = ['title', 'due_date', 'created_at'];
    $allowedOrder = ['asc', 'desc'];

    $sort = 'created_at';
    $order = 'asc';

    if (!empty($_GET['sort']) && validateAllowedValue($_GET['sort'], $allowedSort)) {
        $sort = $_GET['sort'];
    }

    if (!empty($_GET['order']) && validateAllowedValue(strtolower($_GET['order']), $allowedOrder)) {
        $order = strtolower($_GET['order']);
    }

    $sql .= " ORDER BY {$sort} " . strtoupper($order);

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }

    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($assignments as &$assignment) {
        $assignment['files'] = $assignment['files']
            ? json_decode($assignment['files'], true)
            : [];
    }

    sendResponse([
        'success' => true,
        'data'    => $assignments
    ]);
}

/**
 * Get a single assignment by ID
 */
function getAssignmentById(PDO $db, $assignmentId)
{
    if (empty($assignmentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment ID is required.'
        ], 400);
    }

    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();

    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment not found.'
        ], 404);
    }

    $assignment['files'] = $assignment['files']
        ? json_decode($assignment['files'], true)
        : [];

    sendResponse([
        'success' => true,
        'data'    => $assignment
    ]);
}

/**
 * Create a new assignment
 */
function createAssignment(PDO $db, array $data)
{
    // Validate required fields
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse([
            'success' => false,
            'message' => 'title, description and due_date are required.'
        ], 400);
    }

    // Sanitize input
    $title       = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate     = sanitizeInput($data['due_date']);

    // Validate date
    if (!validateDate($dueDate)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid date format. Expected YYYY-MM-DD.'
        ], 400);
    }

    // Handle files
    $filesArray = [];
    if (isset($data['files']) && is_array($data['files'])) {
        $filesArray = $data['files'];
    }
    $filesJson = json_encode($filesArray);

    // Insert query
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':due_date', $dueDate, PDO::PARAM_STR);
    $stmt->bindValue(':files', $filesJson, PDO::PARAM_STR);

    if (!$stmt->execute()) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create assignment.'
        ], 500);
    }

    $newId = $db->lastInsertId();

    $created = [
        'id'          => (int)$newId,
        'title'       => $title,
        'description' => $description,
        'due_date'    => $dueDate,
        'files'       => $filesArray
    ];

    sendResponse([
        'success' => true,
        'data'    => $created
    ], 201);
}

/**
 * Update an existing assignment
 */
function updateAssignment(PDO $db, array $data)
{
    if (empty($data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment ID is required.'
        ], 400);
    }

    $assignmentId = (int)$data['id'];

    // Check if assignment exists
    $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment not found.'
        ], 404);
    }

    $fields = [];
    $params = [':id' => $assignmentId];

    if (isset($data['title'])) {
        $fields[]           = 'title = :title';
        $params[':title']   = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $fields[]                 = 'description = :description';
        $params[':description']   = sanitizeInput($data['description']);
    }

    if (isset($data['due_date'])) {
        $dueDate = sanitizeInput($data['due_date']);
        if (!validateDate($dueDate)) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid date format. Expected YYYY-MM-DD.'
            ], 400);
        }
        $fields[]             = 'due_date = :due_date';
        $params[':due_date']  = $dueDate;
    }

    if (isset($data['files'])) {
        $filesArray = is_array($data['files']) ? $data['files'] : [];
        $filesJson  = json_encode($filesArray);
        $fields[]               = 'files = :files';
        $params[':files']       = $filesJson;
    }

    if (empty($fields)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields to update.'
        ], 400);
    }

    $sql = "UPDATE assignments SET " . implode(', ', $fields) . ", updated_at = NOW()
            WHERE id = :id";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }

    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);

    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        sendResponse([
            'success' => true,
            'message' => 'No changes applied.'
        ]);
    } else {
        sendResponse([
            'success' => true,
            'message' => 'Assignment updated successfully.'
        ]);
    }
}

/**
 * Delete an assignment
 */
function deleteAssignment(PDO $db, $assignmentId)
{
    if (empty($assignmentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment ID is required.'
        ], 400);
    }

    // Check existence
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment not found.'
        ], 404);
    }

    // Delete comments first
    $delComments = $db->prepare("DELETE FROM comments WHERE assignment_id = :id");
    $delComments->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $delComments->execute();

    // Delete assignment
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete assignment.'
        ], 500);
    }

    sendResponse([
        'success' => true,
        'message' => 'Assignment deleted successfully.'
    ]);
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Get all comments for a specific assignment
 */
function getCommentsByAssignment(PDO $db, $assignmentId)
{
    if (empty($assignmentId)) {
        sendResponse([
            'success' => false,
            'message' => 'assignment_id is required.'
        ], 400);
    }

    $stmt = $db->prepare("SELECT * FROM comments WHERE assignment_id = :assignment_id ORDER BY created_at ASC");
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();

    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $comments
    ]);
}

/**
 * Create a new comment
 */
function createComment(PDO $db, array $data)
{
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse([
            'success' => false,
            'message' => 'assignment_id, author and text are required.'
        ], 400);
    }

    $assignmentId = (int)$data['assignment_id'];
    $author       = sanitizeInput($data['author']);
    $text         = trim($data['text']);

    if ($text === '') {
        sendResponse([
            'success' => false,
            'message' => 'Comment text cannot be empty.'
        ], 400);
    }

    // Verify assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment not found for this comment.'
        ], 404);
    }

    $sql = "INSERT INTO comments (assignment_id, author, text, created_at)
            VALUES (:assignment_id, :author, :text, NOW())";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $stmt->bindValue(':author', $author, PDO::PARAM_STR);
    $stmt->bindValue(':text', $text, PDO::PARAM_STR);

    if (!$stmt->execute()) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create comment.'
        ], 500);
    }

    $commentId = $db->lastInsertId();

    $created = [
        'id'            => (int)$commentId,
        'assignment_id' => $assignmentId,
        'author'        => $author,
        'text'          => $text
    ];

    sendResponse([
        'success' => true,
        'data'    => $created
    ], 201);
}

/**
 * Delete a comment
 */
function deleteComment(PDO $db, $commentId)
{
    if (empty($commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Comment ID is required.'
        ], 400);
    }

    // Check existence
    $checkStmt = $db->prepare("SELECT id FROM comments WHERE id = :id");
    $checkStmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Comment not found.'
        ], 404);
    }

    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete comment.'
        ], 500);
    }

    sendResponse([
        'success' => true,
        'message' => 'Comment deleted successfully.'
    ]);
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // Get resource from query string
    $resource = $queryParams['resource'] ?? null;

    if (!$resource) {
        sendResponse([
            'success' => false,
            'message' => 'Resource parameter is required.'
        ], 400);
    }

    if ($method === 'GET') {

        if ($resource === 'assignments') {
            if (!empty($queryParams['id'])) {
                getAssignmentById($db, (int)$queryParams['id']);
            } else {
                getAllAssignments($db);
            }

        } elseif ($resource === 'comments') {
            if (empty($queryParams['assignment_id'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'assignment_id is required for comments.'
                ], 400);
            }
            getCommentsByAssignment($db, (int)$queryParams['assignment_id']);

        } else {
            sendResponse([
                'success' => false,
                'message' => 'Invalid resource.'
            ], 400);
        }

    } elseif ($method === 'POST') {

        if ($resource === 'assignments') {
            createAssignment($db, $data);

        } elseif ($resource === 'comments') {
            createComment($db, $data);

        } else {
            sendResponse([
                'success' => false,
                'message' => 'Invalid resource.'
            ], 400);
        }

    } elseif ($method === 'PUT') {

        if ($resource === 'assignments') {
            updateAssignment($db, $data);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'PUT not supported for this resource.'
            ], 405);
        }

    } elseif ($method === 'DELETE') {

        if ($resource === 'assignments') {
            $id = $queryParams['id'] ?? ($data['id'] ?? null);
            deleteAssignment($db, (int)$id);

        } elseif ($resource === 'comments') {
            $id = $queryParams['id'] ?? null;
            deleteComment($db, (int)$id);

        } else {
            sendResponse([
                'success' => false,
                'message' => 'Invalid resource.'
            ], 400);
        }

    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed.'
        ], 405);
    }

} catch (PDOException $e) {
    sendResponse([
        'success' => false,
        'message' => 'Database error.',
        'error'   => $e->getMessage()
    ], 500);

} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Server error.',
        'error'   => $e->getMessage()
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Send JSON response
 */
function sendResponse($data, int $statusCode = 200)
{
    http_response_code($statusCode);

    if (!is_array($data)) {
        $data = ['data' => $data];
    }

    echo json_encode($data);
    exit;
}

/**
 * Sanitize string input
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $data;
}

/**
 * Validate date (YYYY-MM-DD)
 */
function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate allowed values (e.g. sort / order)
 */
function validateAllowedValue($value, array $allowedValues)
{
    return in_array($value, $allowedValues, true);
}

?>
