<?php
require 'db.php';
if (!isset($_SESSION['user_id'])) {
 header("Location: login.php");
 exit;
}

$attempt_id = isset($_GET['attempt_id']) ? htmlspecialchars($_GET['attempt_id']) : '';
if (!$attempt_id) {
 header("Location: student-dashboard.php");
 exit;
}

// Fetch Attempt Data
$stmtHist = $pdo->prepare("SELECT h.*, t.title, t.time_limit, u.name as student_name FROM history h JOIN tests t ON h.test_id = t.id JOIN users u ON h.student_id = u.id WHERE h.attempt_id = ?");
$stmtHist->execute([$attempt_id]);
$attempt = $stmtHist->fetch();

if (!$attempt) {
 die("Attempt not found.");
}

// Fetch Questions
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE test_id = ?");
$stmtQ->execute([$attempt['test_id']]);
$questions = $stmtQ->fetchAll();

// Fetch Answers (Since answers are stored simply in history, we will infer the breakdown. To do this perfectly, you would technically need an `attempt_answers` table. For this analysis page to work dynamically without altering your DB schema again, we will show the statistics breakdown and the questions).
?>
<!doctype html>
<html lang="en">

<head>
 <meta charset="UTF-8">
 <title>Analysis Report</title>
 <link
  rel="icon"
  type="image/x-icon"
  href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
 <link rel="stylesheet" href="style.css" />
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
 <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>

<body style="background: #f1f5f9;">
 <div class="navbar">
  <h2><i class="fa-solid fa-chart-line"></i> Report Analysis</h2>
  <button class="btn-outline" onclick="window.location.href='<?= $_SESSION['role'] === 'admin' ? 'admin-dashboard.php' : 'student-profile.php' ?>'"><i class="fa-solid fa-house"></i> Dashboard</button>
 </div>

 <div class="container slide-up">
  <div class="card" style="padding: 48px; margin-bottom: 40px; display: flex; flex-wrap: wrap; gap: 50px; background: #fff;">
   <div style="flex: 1; min-width: 300px;">
    <p style="color: #64748b; font-weight: 600; margin-bottom: 12px; text-transform: uppercase;">Student: <?= htmlspecialchars($attempt['student_name']) ?></p>
    <h2 style="font-size: 2rem; color: #0f172a; margin-bottom: 24px; font-weight: 800;"><?= htmlspecialchars($attempt['title']) ?></h2>

    <div style="display: inline-block; background: #f8fafc; padding: 16px 32px; border-radius: 12px; border: 2px solid #e2e8f0; margin-bottom: 16px;">
     <span style="font-size: 1rem; color: #64748b; display: block; margin-bottom: 8px; font-weight: 600;">Net Score Achieved</span>
     <span style="font-size: 2.5rem; font-weight: 800; color: #3b82f6;"><?= $attempt['score'] ?></span>
    </div>

    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px;">
     <div class="badge bg-gray"><i class="fa-solid fa-stopwatch"></i> Limit: <?= $attempt['time_limit'] ?>m</div>
     <div class="badge bg-blue"><i class="fa-solid fa-clock-rotate-left"></i> Taken: <?= floor($attempt['time_taken'] / 60) ?>m <?= $attempt['time_taken'] % 60 ?>s</div>
    </div>
   </div>

   <div style="flex: 1; max-width: 280px;">
    <canvas id="scoreChart"></canvas>
   </div>
  </div>

  <h3 class="page-title" style="font-size: 1.5rem;"><i class="fa-solid fa-list-check"></i> Answer Key Reference</h3>
  <div style="display: flex; flex-direction: column; gap: 20px;">
   <?php foreach ($questions as $index => $q): ?>
    <div class="card review-card slide-up" style="padding: 30px; border: 1px solid #e2e8f0; border-radius: 16px;">
     <h4 style="color:#0f172a; font-weight:700; font-size: 1.15rem; margin-bottom: 15px;">Q<?= $index + 1 ?>. <?= htmlspecialchars($q['question_text']) ?></h4>
     <div style="background: #f0fdf4; padding: 20px; border-radius: 12px; border: 1px solid #bbf7d0;">
      <p style="color:#15803d; font-size: 1rem; margin: 0;">Correct Answer: <strong>Option <?= $q['correct_option'] ?> ( <?= htmlspecialchars($q['option_' . $q['correct_option']]) ?> )</strong></p>
     </div>
    </div>
   <?php endforeach; ?>
  </div>
 </div>

 <script>
  new Chart(document.getElementById("scoreChart").getContext("2d"), {
   type: "doughnut",
   data: {
    labels: ["Correct", "Wrong", "Skipped"],
    datasets: [{
     data: [<?= $attempt['correct'] ?>, <?= $attempt['wrong'] ?>, <?= $attempt['skipped'] ?>],
     backgroundColor: ["#15803d", "#dc2626", "#e2e8f0"],
     borderWidth: 0
    }]
   },
   options: {
    responsive: true,
    cutout: "75%"
   }
  });
 </script>
</body>

</html>