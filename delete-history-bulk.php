<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
 http_response_code(403);
 exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $action = $_POST['delete_action'] ?? '';
 $subject = $_POST['subject'] ?? '';
 $selected_ids = $_POST['attempt_ids'] ?? [];
 $user_id = $_SESSION['user_id'];
 $role = $_SESSION['role'];

 if ($action === 'all') {
  if ($role === 'admin') {
   $stmt = $pdo->prepare("DELETE h FROM history h JOIN tests t ON h.test_id = t.id WHERE t.subject = ?");
   $stmt->execute([$subject]);
  } else {
   $stmt = $pdo->prepare("UPDATE history h JOIN tests t ON h.test_id = t.id SET h.is_deleted = 1 WHERE h.student_id = ? AND t.subject = ?");
   $stmt->execute([$user_id, $subject]);
  }
 } elseif ($action === 'selected' && !empty($selected_ids)) {
  $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
  if ($role === 'admin') {
   $stmt = $pdo->prepare("DELETE FROM history WHERE attempt_id IN ($placeholders)");
   $stmt->execute($selected_ids);
  } else {
   $params = array_merge($selected_ids, [$user_id]);
   $stmt = $pdo->prepare("UPDATE history SET is_deleted = 1 WHERE attempt_id IN ($placeholders) AND student_id = ?");
   $stmt->execute($params);
  }
 } elseif ($action === 'unselected') {
  if ($role === 'admin') {
   if (empty($selected_ids)) {
    $stmt = $pdo->prepare("DELETE h FROM history h JOIN tests t ON h.test_id = t.id WHERE t.subject = ?");
    $stmt->execute([$subject]);
   } else {
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $params = array_merge([$subject], $selected_ids);
    $stmt = $pdo->prepare("DELETE h FROM history h JOIN tests t ON h.test_id = t.id WHERE t.subject = ? AND h.attempt_id NOT IN ($placeholders)");
    $stmt->execute($params);
   }
  } else {
   if (empty($selected_ids)) {
    $stmt = $pdo->prepare("UPDATE history h JOIN tests t ON h.test_id = t.id SET h.is_deleted = 1 WHERE h.student_id = ? AND t.subject = ?");
    $stmt->execute([$user_id, $subject]);
   } else {
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $params = array_merge([$user_id, $subject], $selected_ids);
    $stmt = $pdo->prepare("UPDATE history h JOIN tests t ON h.test_id = t.id SET h.is_deleted = 1 WHERE h.student_id = ? AND t.subject = ? AND h.attempt_id NOT IN ($placeholders)");
    $stmt->execute($params);
   }
  }
 }

 $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
 header("Location: " . $referer);
 exit;
}
