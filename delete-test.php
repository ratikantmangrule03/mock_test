<?php
require 'db.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
 http_response_code(403);
 exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_id'])) {
 $test_id = $_POST['test_id'];

 // Delete the test. (CASCADE handles removing related questions and history)
 $stmt = $pdo->prepare("DELETE FROM tests WHERE id = ?");
 $stmt->execute([$test_id]);

 // Redirect back to the subject page
 $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'admin-dashboard.php';
 header("Location: " . $referer);
 exit;
}
