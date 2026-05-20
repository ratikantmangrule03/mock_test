<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
 header("Location: login.php");
 exit;
}

$test_id = isset($_GET['test_id']) ? htmlspecialchars($_GET['test_id']) : '';
if (!$test_id) {
 header("Location: student-dashboard.php");
 exit;
}

$stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
$stmt->execute([$test_id]);
$test = $stmt->fetch();

$qCount = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE test_id = ?");
$qCount->execute([$test_id]);
$total_questions = $qCount->fetchColumn();
?>
<!doctype html>
<html lang="en">

<head>
 <meta charset="UTF-8">
 <title>Instructions</title>
 <link
  rel="icon"
  type="image/x-icon"
  href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
 <link rel="stylesheet" href="style.css" />
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>

<body style="background: #f1f5f9;">
 <div class="center-wrap">
  <div class="card slide-up" style="width: 100%; max-width: 750px; padding: 48px">
   <h1 style="font-size: 1.8rem; margin-bottom: 32px; font-weight: 700"><i class="fa-solid fa-file-signature"></i> Test Instructions:</h1>

   <div style="background: #f8fafc; padding: 30px; border: 2px solid #e2e8f0; border-radius: 16px; margin-bottom: 32px;">
    <ul style="padding-left: 20px; color: #475569; line-height: 2.2; font-size: 1rem;">
     <li>Total Questions: <strong style="color: #0f172a"><?= $total_questions ?></strong></li>
     <li>Time Limit: <strong style="color: #0f172a"><?= $test['time_limit'] ?> Mins</strong></li>
     <li>Passing Marks: <strong style="color: #0f172a"><?= $test['pass_marks'] ?></strong></li>
     <li>Correct Answer: <strong style="color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 6px;">+1 Mark</strong></li>
     <li>Wrong Answer: <strong style="color: #dc2626; background: #fee2e2; padding: 2px 8px; border-radius: 6px;">-5 Marks</strong></li>
     <li style="color: #b91c1c; margin-top: 16px; background: #fef2f2; padding: 12px; border-radius: 8px; border: 1px solid #fecaca; list-style-type: none; margin-left: -20px;">
      <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px"></i>
      <strong>Security Alert:</strong> The exam will auto-submit when the timer runs out. Switching tabs or minimizing the browser will also automatically submit your exam to prevent malpractice.
     </li>
    </ul>
   </div>

   <div id="agreeContainer" style="margin-bottom: 32px; background: #eff6ff; padding: 20px; border-radius: 12px; border: 2px solid #bfdbfe; transition: all 0.3s;">
    <label style="display: flex; align-items: center; gap: 16px; cursor: pointer; color: #1e3a8a; font-weight: 600;">
     <input type="checkbox" id="agreeBtn" style="width: 24px; height: 24px;" onchange="toggleStyle()" />
     I have read and understood all the instructions.
    </label>
   </div>

   <div style="display: flex; gap: 16px; justify-content: flex-end">
    <button class="btn-outline" onclick="window.location.href='student-dashboard.php'"><i class="fa-solid fa-xmark"></i> Cancel</button>
    <button class="btn-primary" onclick="startTest()"><i class="fa-solid fa-play"></i> Start Test</button>
   </div>
  </div>
 </div>

 <script>
  function toggleStyle() {
   const box = document.getElementById("agreeContainer");
   if (document.getElementById("agreeBtn").checked) {
    box.style.background = "#dcfce7";
    box.style.borderColor = "#bbf7d0";
   } else {
    box.style.background = "#eff6ff";
    box.style.borderColor = "#bfdbfe";
   }
  }

  function startTest() {
   if (!document.getElementById("agreeBtn").checked) {
    alert("You must agree to the instructions first.");
   } else {
    window.location.href = 'exam.php?test_id=<?= $test_id ?>';
   }
  }
 </script>
</body>

</html>