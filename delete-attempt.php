<?php
require 'db.php';

// Ensure the user is logged in (both students and admins are allowed now)
if (!isset($_SESSION['user_id'])) {
 http_response_code(403);
 exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attempt_id'])) {
 $attempt_id = $_POST['attempt_id'];

 if ($_SESSION['role'] === 'admin') {
  // ADMIN RULE: Permanently delete the attempt from the database completely
  $stmt = $pdo->prepare("DELETE FROM history WHERE attempt_id = ?");
  $stmt->execute([$attempt_id]);
 } else {
  // STUDENT RULE: Soft delete (hide it from their history view, but keep for admin stats)
  $student_id = $_SESSION['user_id'];
  $stmt = $pdo->prepare("UPDATE history SET is_deleted = 1 WHERE attempt_id = ? AND student_id = ?");
  $stmt->execute([$attempt_id, $student_id]);
 }

 // Redirect back to the previous page where the user clicked delete
 $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
 header("Location: " . $referer);
 exit;
}
