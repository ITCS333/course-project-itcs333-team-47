<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

session_start();

// Protect admin API
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    sendResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =======================================================
// Database Connection
// =======================================================
class Database {
    private $host = 'localhost';
    private $db_name = 'your_db';
    private $username = 'your_user';
    private $password = 'your_pass';
    private $conn;

    public function getConnection() {
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

// =======================================================
// Helper Functions
// =======================================================
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data))); 
}

// =======================================================
// Initialize
// =======================================================
$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

// REQUIRED FIX: Missing variables
$studentId = $_GET['student_id'] ?? null;
$action    = $_GET['action'] ?? null;


// =======================================================
// GET: All Students OR Specific Student
// =======================================================
function getStudents($db) {
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

function getStudentById($db, $studentId) {
    $stmt = $db->prepare("SELECT student_id, name, email FROM students WHERE student_id = :id");
    $stmt->bindParam(':id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) sendResponse(['success' => true, 'data' => $student]);
    else sendResponse(['error' => 'Student not found'], 404);
}


// =======================================================
// POST: Create Student
// =======================================================
function createStudent($db, $data) {
    $student_id = sanitizeInput($data['student_id'] ?? '');
    $name = sanitizeInput($data['name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$student_id || !$name || !$email || !$password) sendResponse(['error' => 'Missing required fields'], 400);
    if (!validateEmail($email)) sendResponse(['error' => 'Invalid email'], 400);

    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid OR email = :em");
    $stmt->execute([':sid'=>$student_id, ':em'=>$email]);
    if ($stmt->rowCount() > 0) sendResponse(['error' => 'Student ID or email already exists'], 409);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO students (student_id, name, email, password, created_at) VALUES (:sid,:name,:email,:pwd,NOW())");

    if ($stmt->execute([':sid'=>$student_id, ':name'=>$name, ':email'=>$email, ':pwd'=>$hash])) {
        sendResponse(['success'=>true, 'message'=>'Student created'], 201);
    } else {
        sendResponse(['error'=>'Failed to create student'],500);
    }
}


// =======================================================
// PUT: Update Student
// =======================================================
function updateStudent($db, $data) {
    $student_id = sanitizeInput($data['student_id'] ?? '');
    if (!$student_id) sendResponse(['error' => 'student_id is required'], 400);

    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid");
    $stmt->execute([':sid' => $student_id]);
    if ($stmt->rowCount() == 0) sendResponse(['error' => 'Student not found'], 404);

    $fields = [];
    $params = [':sid' => $student_id];

    if (!empty($data['name'])) {
        $fields[] = "name = :name"; 
        $params[':name'] = sanitizeInput($data['name']); 
    }

    if (!empty($data['email'])) {
        if (!validateEmail($data['email'])) sendResponse(['error' => 'Invalid email'], 400);

        $stmt2 = $db->prepare("SELECT * FROM students WHERE email = :email AND student_id != :sid");
        $stmt2->execute([':email'=>$data['email'], ':sid'=>$student_id]);
        if ($stmt2->rowCount() > 0) sendResponse(['error' => 'Email already exists'], 409);

        $fields[] = "email = :email"; 
        $params[':email'] = sanitizeInput($data['email']);
    }

    if (!$fields) sendResponse(['error' => 'No fields to update'], 400);

    $sql = "UPDATE students SET ".implode(", ", $fields)." WHERE student_id = :sid";
    $stmt = $db->prepare($sql);

    if ($stmt->execute($params)) sendResponse(['success' => true]);
    else sendResponse(['error' => 'Failed to update student'], 500);
}


// =======================================================
// DELETE
// =======================================================
function deleteStudent($db, $studentId) {
    if (!$studentId) sendResponse(['error' => 'student_id is required'], 400);

    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid");
    $stmt->execute([':sid'=>$studentId]);
    if ($stmt->rowCount() == 0) sendResponse(['error' => 'Student not found'], 404);

    $stmt = $db->prepare("DELETE FROM students WHERE student_id = :sid");
    if ($stmt->execute([':sid'=>$studentId])) sendResponse(['success' => true]);
    else sendResponse(['error'=>'Failed to delete student'],500);
}


// =======================================================
// Change Password
// =======================================================
function changePassword($db, $data) {
    $student_id = sanitizeInput($data['student_id'] ?? '');
    $current_password = $data['current_password'] ?? '';
    $new_password = $data['new_password'] ?? '';

    if (!$student_id || !$current_password || !$new_password)
        sendResponse(['error'=>'Missing required fields'],400);

    if (strlen($new_password)<8)
        sendResponse(['error'=>'Password must be at least 8 characters'],400);

    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :sid");
    $stmt->execute([':sid'=>$student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) sendResponse(['error'=>'Student not found'],404);
    if (!password_verify($current_password, $row['password']))
        sendResponse(['error'=>'Current password incorrect'],401);

    $hash = password_hash($new_password,PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE students SET password = :pwd WHERE student_id = :sid");
    if ($stmt->execute([':pwd'=>$hash, ':sid'=>$student_id]))
        sendResponse(['success'=>true,'message'=>'Password updated']);
    else
        sendResponse(['error'=>'Failed to update password'],500);
}


// =======================================================
// Main Router
// =======================================================
try {

    if ($method === 'GET') {
        if ($studentId) getStudentById($db, $studentId);
        else getStudents($db);

    } elseif ($method === 'POST') {
        if ($action === 'change_password') changePassword($db, $input);
        else createStudent($db, $input);

    } elseif ($method === 'PUT') {
        updateStudent($db, $input);

    } elseif ($method === 'DELETE') {
        deleteStudent($db, $studentId);

    } else {
        sendResponse(['error' => 'Method not allowed'], 405);
    }

} catch (PDOException $e) {
    sendResponse(['error' => 'Database error occurred'], 500);

} catch (Exception $e) {
    sendResponse(['error' => 'An error occurred'], 500);
}

?>
