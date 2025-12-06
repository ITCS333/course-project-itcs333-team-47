<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once '../config/Database.php';

// Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection();

// Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$input = json_decode(file_get_contents('php://input'), true);

// Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = $_GET['resource'] ?? 'weeks';

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // Initialize variables for search, sort, and order from query parameters
    $search = $_GET['search'] ?? null;
    $sort = $_GET['sort'] ?? 'start_date';
    $order = $_GET['order'] ?? 'asc';
    
    // Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $query = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    
    // Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
    $params = [];
    if ($search) {
        $query .= " WHERE title LIKE ? OR description LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    $allowedSortFields = ['title', 'start_date', 'created_at'];
    if (!in_array($sort, $allowedSortFields)) {
        $sort = 'start_date';
    }
    
    // Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)
    if (!in_array($order, ['asc', 'desc'])) {
        $order = 'asc';
    }
    
    // Add ORDER BY clause to the query
    $query .= " ORDER BY $sort $order";
    
    // Prepare the SQL query using PDO
    $stmt = $db->prepare($query);
    
    // Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    
    // Execute the query
    $stmt->execute($params);
    
    // Fetch all results as an associative array
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach ($weeks as &$week) {
        $week['links'] = json_decode($week['links'], true);
    }
    
    // Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse(['success' => true, 'data' => $weeks]);
}

/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId) {
        sendError('week_id is required', 400);
    }
    
    // Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $stmt = $db->prepare("SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?");
    
    // Bind the week_id parameter
    $stmt->execute([$weekId]);
    
    // Fetch the result
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
    if ($week) {
        $week['links'] = json_decode($week['links'], true);
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        sendError('Week not found', 404);
    }
}

/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            sendError("$field is required", 400);
        }
    }
    
    // Sanitize input data
    // Trim whitespace from title, description, and week_id
    $weekId = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    $startDate = $data['start_date'];
    $description = sanitizeInput($data['description']);
    
    // Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
    if (!validateDate($startDate)) {
        sendError('Invalid start_date format. Use YYYY-MM-DD', 400);
    }
    
    // Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $stmt = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $stmt->execute([$weekId]);
    if ($stmt->fetch()) {
        sendError('Week ID already exists', 409);
    }
    
    // Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);
    
    // Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
    $stmt = $db->prepare("INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)");
    
    // Bind parameters
    
    // Execute the query
    if ($stmt->execute([$weekId, $title, $startDate, $description, $links])) {
        // Check if insert was successful
        // If yes, return success response with 201 status (Created) and the new week data
        // If no, return error response with 500 status
        $newWeek = [
            'week_id' => $weekId,
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

/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // Validate that week_id is provided
    // If not, return error response with 400 status
    $weekId = $data['week_id'] ?? null;
    if (!$weekId) {
        sendError('week_id is required', 400);
    }
    
    // Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT * FROM weeks WHERE week_id = ?");
    $stmt->execute([$weekId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendError('Week not found', 404);
    }
    
    // Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $setClauses = [];
    $params = [];
    
    // Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    // If start_date is provided, validate format and add "start_date = ?"
    // If description is provided, add "description = ?"
    // If links is provided, encode to JSON and add "links = ?"
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
    
    // If no fields to update, return error response with 400 status
    if (empty($setClauses)) {
        sendError('No fields to update', 400);
    }
    
    // Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    
    // Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    $query = "UPDATE weeks SET " . implode(', ', $setClauses) . " WHERE week_id = ?";
    
    // Prepare the query
    $stmt = $db->prepare($query);
    
    // Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    $params[] = $weekId;
    
    // Execute the query
    if ($stmt->execute($params)) {
        // Check if update was successful
        // If yes, return success response with updated week data
        // If no, return error response with 500 status
        $stmt = $db->prepare("SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?");
        $stmt->execute([$weekId]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        $updated['links'] = json_decode($updated['links'], true);
        sendResponse(['success' => true, 'data' => $updated]);
    } else {
        sendError('Failed to update week', 500);
    }
}

/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId) {
        sendError('week_id is required', 400);
    }
    
    // Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $stmt->execute([$weekId]);
    if (!$stmt->fetch()) {
        sendError('Week not found', 404);
    }
    
    // Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
    $stmt = $db->prepare("DELETE FROM comments WHERE week_id = ?");
    $stmt->execute([$weekId]);
    
    // Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $stmt = $db->prepare("DELETE FROM weeks WHERE week_id = ?");
    
    // Bind the week_id parameter
    
    // Execute the query
    if ($stmt->execute([$weekId])) {
        // Check if delete was successful
        // If yes, return success response with message indicating week and comments deleted
        // If no, return error response with 500 status
        sendResponse(['success' => true, 'message' => 'Week and associated comments deleted']);
    } else {
        sendError('Failed to delete week', 500);
    }
}

// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId) {
        sendError('week_id is required', 400);
    }
    
    // Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $stmt = $db->prepare("SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC");
    
    // Bind the week_id parameter
    $stmt->execute([$weekId]);
    
    // Fetch all results as an associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response with success status and data
    // Even if no comments exist, return an empty array
    sendResponse(['success' => true, 'data' => $comments]);
}

/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            sendError("$field is required", 400);
        }
    }
    
    // Sanitize input data
    // Trim whitespace from all fields
    $weekId = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if (empty($text)) {
        sendError('Comment text cannot be empty', 400);
    }
    
    // Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not
