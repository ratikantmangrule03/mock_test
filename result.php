<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
 header("Location: login.php");
 exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $student_id = $_SESSION['user_id'];
 $test_id = $_POST['test_id'];
 $time_taken = (int)$_POST['time_taken'];
 $submitted_answers = isset($_POST['answers']) ? $_POST['answers'] : [];

 $stmtTest = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
 $stmtTest->execute([$test_id]);
 $test = $stmtTest->fetch();

 $stmtQ = $pdo->prepare("SELECT id, correct_option FROM questions WHERE test_id = ?");
 $stmtQ->execute([$test_id]);
 $questions = $stmtQ->fetchAll();

 $correct = 0;
 $wrong = 0;
 $skipped = 0;

 // Evaluate Answers
 foreach ($questions as $q) {
  $q_id = $q['id'];
  if (!isset($submitted_answers[$q_id])) {
   $skipped++;
  } elseif ((int)$submitted_answers[$q_id] === (int)$q['correct_option']) {
   $correct++;
  } else {
   $wrong++;
  }
 }

 // New Scoring Logic from your index.html (+1 for correct, -5 for wrong)
 $score = ($correct * 1) - ($wrong * 5);
 $attempt_id = uniqid('att_');

 // Save Attempt to Database
 $stmtHistory = $pdo->prepare("INSERT INTO history (attempt_id, test_id, student_id, score, correct, wrong, skipped, time_taken) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
 $stmtHistory->execute([$attempt_id, $test_id, $student_id, $score, $correct, $wrong, $skipped, $time_taken]);
} else {
 // If accessed directly without submitting, just grab the most recent attempt
 $stmtLast = $pdo->prepare("SELECT * FROM history WHERE student_id = ? ORDER BY attempt_date DESC LIMIT 1");
 $stmtLast->execute([$_SESSION['user_id']]);
 $lastAttempt = $stmtLast->fetch();

 if (!$lastAttempt) {
  header("Location: student-dashboard.php");
  exit;
 }

 $attempt_id = $lastAttempt['attempt_id'];
 $score = $lastAttempt['score'];
 $correct = $lastAttempt['correct'];
 $wrong = $lastAttempt['wrong'];
 $skipped = $lastAttempt['skipped'];
}
?>
<!doctype html>
<html lang="en">

<head>
 <meta charset="UTF-8">
 <title>Test Submitted</title>
 <link
  rel="icon"
  type="image/x-icon"
  href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
 <link rel="stylesheet" href="style.css" />
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>

<body style="background: #f1f5f9;">
 <div class="center-wrap" style="height: 100vh;">
  <div class="card text-center slide-up" style="width: 100%; max-width: 650px; padding: 50px 40px">
   <div style="width: 80px; height: 80px; background: #dcfce7; color: #15803d; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; margin-bottom: 24px;">
    <i class="fa-solid fa-trophy"></i>
   </div>
   <h1 style="font-size: 2rem; margin-bottom: 8px; font-weight: 700">Test Submitted</h1>
   <p style="color: #64748b; margin-bottom: 40px; font-size: 1.05rem">Your performance has been successfully recorded.</p>

   <h2 style="font-size: 5rem; font-weight: 800; color: #0f172a; margin-bottom: 40px; line-height: 1;"><?= $score ?></h2>

   <div style="display: flex; gap: 20px; justify-content: center; margin-bottom: 48px;">
    <div style="background: #f0fdf4; border: 2px solid #bbf7d0; color: #15803d; padding: 20px; border-radius: 16px; flex: 1;">
     <h3 style="font-size: 1.8rem; margin-bottom: 4px; font-weight: 800"><?= $correct ?></h3>
     <p style="font-size: 0.9rem; font-weight: 600"><i class="fa-solid fa-circle-check"></i> Correct</p>
    </div>
    <div style="background: #fef2f2; border: 2px solid #fecaca; color: #dc2626; padding: 20px; border-radius: 16px; flex: 1;">
     <h3 style="font-size: 1.8rem; margin-bottom: 4px; font-weight: 800"><?= $wrong ?></h3>
     <p style="font-size: 0.9rem; font-weight: 600"><i class="fa-solid fa-circle-xmark"></i> Wrong</p>
    </div>
    <div style="background: #f8fafc; border: 2px solid #e2e8f0; color: #475569; padding: 20px; border-radius: 16px; flex: 1;">
     <h3 style="font-size: 1.8rem; margin-bottom: 4px; font-weight: 800"><?= $skipped ?></h3>
     <p style="font-size: 0.9rem; font-weight: 600"><i class="fa-solid fa-minus"></i> Skipped</p>
    </div>
   </div>

   <div style="display: flex; gap: 20px; justify-content: center">
    <button class="btn-outline" style="padding: 14px 28px" onclick="window.location.href='student-dashboard.php'">
     <i class="fa-solid fa-house"></i> Dashboard
    </button>
    <button class="btn-primary" style="padding: 14px 28px" onclick="window.location.href='analysis.php?attempt_id=<?= $attempt_id ?>'">
     <i class="fa-solid fa-chart-pie"></i> Detailed Analysis
    </button>
   </div>
  </div>
 </div>
</body>

</html>