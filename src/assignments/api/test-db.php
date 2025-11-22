<?php
require_once "../common/Database/Database.php";

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    echo "✅ Database connection successful!";
} else {
    echo "❌ Database connection failed!";
}
?>
