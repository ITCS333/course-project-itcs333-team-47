<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance


// TODO: Get the PDO database connection


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()


// TODO: Parse query parameters for filtering and searching


/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */

// Database connection
class Database {
    private $host = 'localhost';
    private $db_name = 'your_db_name';
    private $username = 'your_db_user';
    private $password = 'your_db_password';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            sendResponse(['error' => 'Database connection failed'], 500);
        }
        return $this->conn;
    }
}

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$studentId = $_GET['student_id'] ?? null;
$action = $_GET['action'] ?? null;

// Helper functions
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}



function getStudents($db) {
    
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    
    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    
    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    
    // TODO: Bind parameters if using search
    
    // TODO: Execute the query
    
    // TODO: Fetch all results as an associative array
    
    // TODO: Return JSON response with success status and data
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'name';
    $order = strtolower($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

    $allowedSort = ['name', 'student_id', 'email'];
    if (!in_array($sort, $allowedSort)) $sort = 'name';

    $sql = "SELECT student_id, name, email FROM students";
    $params = [];
    if ($search) {
        $sql .= " WHERE name LIKE :s OR student_id LIKE :s OR email LIKE :s";
        $params[':s'] = "%$search%";
    }
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $result]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    
    // TODO: Bind the student_id parameter
    
    // TODO: Execute the query
    
    // TODO: Fetch the result
    
    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
    $stmt = $db->prepare("SELECT student_id, name, email FROM students WHERE student_id = :id");
    $stmt->bindParam(':id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) sendResponse(['success' => true, 'data' => $student]);
    else sendResponse(['error' => 'Student not found'], 404);
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    
    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    
    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    
    // TODO: Prepare INSERT query
    
    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    
    // TODO: Execute the query
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status
     $student_id = sanitizeInput($data['student_id'] ?? '');
    $name = sanitizeInput($data['name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$student_id || !$name || !$email || !$password) sendResponse(['error' => 'Missing required fields'], 400);
    if (!validateEmail($email)) sendResponse(['error' => 'Invalid email'], 400);

    // Check duplicates
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid OR email = :em");
    $stmt->execute([':sid'=>$student_id, ':em'=>$email]);
    if ($stmt->rowCount() > 0) sendResponse(['error' => 'Student ID or email already exists'], 409);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO students (student_id, name, email, password, created_at) VALUES (:sid,:name,:email,:pwd,NOW())");
    $stmt->bindParam(':sid', $student_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':pwd', $hash);
    if ($stmt->execute()) sendResponse(['success' => true, 'message' => 'Student created'], 201);
    else sendResponse(['error' => 'Failed to create student'], 500);
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    
    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    
    // TODO: If email is being updated, check if new email already exists
    // Prepare and execute a SELECT query
    // Exclude the current student from the check
    // If duplicate found, return error response with 409 status
    
    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
    
    // TODO: Execute the query
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    $student_id = sanitizeInput($data['student_id'] ?? '');
    if (!$student_id) sendResponse(['error' => 'student_id is required'], 400);

    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid");
    $stmt->bindParam(':sid', $student_id);
    $stmt->execute();
    if ($stmt->rowCount() == 0) sendResponse(['error' => 'Student not found'], 404);

    $fields = [];
    $params = [':sid' => $student_id];

    if (!empty($data['name'])) { $fields[] = "name = :name"; $params[':name'] = sanitizeInput($data['name']); }
    if (!empty($data['email'])) {
        if (!validateEmail($data['email'])) sendResponse(['error' => 'Invalid email'], 400);
        // Check if email exists
        $stmt2 = $db->prepare("SELECT * FROM students WHERE email = :email AND student_id != :sid");
        $stmt2->execute([':email'=>$data['email'], ':sid'=>$student_id]);
        if ($stmt2->rowCount() > 0) sendResponse(['error' => 'Email already exists'], 409);
        $fields[] = "email = :email"; $params[':email'] = sanitizeInput($data['email']);
    }

    if (!$fields) sendResponse(['error' => 'No fields to update'], 400);

    $sql = "UPDATE students SET ".implode(", ", $fields)." WHERE student_id = :sid";
    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) sendResponse(['success' => true, 'message' => 'Student updated']);
    else sendResponse(['error' => 'Failed to update student'], 500);
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    
    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    
    // TODO: Prepare DELETE query
    
    // TODO: Bind the student_id parameter
    
    // TODO: Execute the query
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if (!$studentId) sendResponse(['error' => 'student_id is required'], 400);
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid");
    $stmt->execute([':sid'=>$studentId]);
    if ($stmt->rowCount() == 0) sendResponse(['error' => 'Student not found'], 404);

    $stmt = $db->prepare("DELETE FROM students WHERE student_id = :sid");
    if ($stmt->execute([':sid'=>$studentId])) sendResponse(['success' => true, 'message' => 'Student deleted']);
    else sendResponse(['error' => 'Failed to delete student'], 500);
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    
    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    
    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    
    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    
    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    
    // TODO: Update password in database
    // Prepare UPDATE query
    
    // TODO: Bind parameters and execute
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
     $student_id = sanitizeInput($data['student_id'] ?? '');
    $current_password = $data['current_password'] ?? '';
    $new_password = $data['new_password'] ?? '';

    if (!$student_id || !$current_password || !$new_password) sendResponse(['error'=>'Missing required fields'],400);
    if (strlen($new_password)<8) sendResponse(['error'=>'Password must be at least 8 characters'],400);

    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :sid");
    $stmt->execute([':sid'=>$student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) sendResponse(['error'=>'Student not found'],404);

    if (!password_verify($current_password, $row['password'])) sendResponse(['error'=>'Current password incorrect'],401);

    $hash = password_hash($new_password,PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE students SET password = :pwd WHERE student_id = :sid");
    if ($stmt->execute([':pwd'=>$hash, ':sid'=>$student_id])) sendResponse(['success'=>true,'message'=>'Password updated']);
    else sendResponse(['error'=>'Failed to update password'],500);
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        
        if ($studentId) {
            getStudentById($db, $studentId);
        } else {
            getStudents($db);
        }

    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
        if ($action === 'change_password') {
            changePassword($db, $input);
        } else {
            createStudent($db, $input);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        updateStudent($db, $input);
        
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        deleteStudent($db, $studentId);
        
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        sendResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    sendResponse(['error' => 'Database error occurred'], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    sendResponse(['error' => 'An error occurred'], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);        
    echo json_encode($data);              
    exit();
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data))); 

}

?>
