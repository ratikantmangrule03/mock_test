<?php
require 'db.php';

// Ensure only admins can upload
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
 http_response_code(403);
 echo json_encode(['error' => 'Unauthorized']);
 exit;
}

// Get the JSON payload from the JavaScript frontend
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['questions']) || count($data['questions']) === 0) {
 http_response_code(400);
 echo json_encode(['error' => 'No valid questions found in the document.']);
 exit;
}

try {
 $pdo->beginTransaction();

 $test_id = uniqid('test_');
 $title = trim($data['title']);
 $subject = trim($data['subject']);
 $time_limit = (int)$data['timeLimit'];
 $pass_marks = (int)$data['passMarks'];
 $test_date = $data['testDate'];

 // Insert the test metadata
 $stmtTest = $pdo->prepare("INSERT INTO tests (id, title, subject, time_limit, pass_marks, test_date) VALUES (?, ?, ?, ?, ?, ?)");
 $stmtTest->execute([$test_id, $title, $subject, $time_limit, $pass_marks, $test_date]);

 // Insert all extracted questions
 $stmtQ = $pdo->prepare("INSERT INTO questions (test_id, question_text, option_1, option_2, option_3, option_4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");

 foreach ($data['questions'] as $q) {
  $stmtQ->execute([
   $test_id,
   trim($q['q']),
   trim($q['opts'][0]),
   trim($q['opts'][1] ?? ''),
   trim($q['opts'][2] ?? ''),
   trim($q['opts'][3] ?? ''),
   (int)$q['ans'] + 1 // Add 1 because JS uses 0-index (0-3) and our DB uses 1-index (1-4)
  ]);
 }

 $pdo->commit();
 echo json_encode(['success' => true, 'message' => 'Test uploaded successfully!']);
} catch (Exception $e) {
 $pdo->rollBack();
 http_response_code(500);
 echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
