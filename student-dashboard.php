<?php
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: login.php");
  exit;
}

$student_id = $_SESSION['user_id'];
$student_name = htmlspecialchars($_SESSION['name']);
$student_initial = substr($student_name, 0, 1);

$stmt = $pdo->prepare("SELECT COUNT(*) as tests_taken, SUM(correct) as t_correct, SUM(wrong) as t_wrong, SUM(skipped) as t_skipped 
                       FROM history WHERE student_id = ? AND is_deleted = FALSE");
$stmt->execute([$student_id]);
$stats = $stmt->fetch();

$tests_taken = $stats['tests_taken'] ?? 0;
$t_correct = $stats['t_correct'] ?? 0;
$t_wrong = $stats['t_wrong'] ?? 0;
$t_skipped = $stats['t_skipped'] ?? 0;
$total_qs = $t_correct + $t_wrong + $t_skipped;
$accuracy = $total_qs > 0 ? round(($t_correct / $total_qs) * 100) : 0;

$subjectsInfo = [
  ['name' => 'Physics', 'colorClass' => 'bg-physics', 'icon' => 'fa-atom'],
  ['name' => 'Chemistry', 'colorClass' => 'bg-chemistry', 'icon' => 'fa-flask'],
  ['name' => 'Biology', 'colorClass' => 'bg-biology', 'icon' => 'fa-dna'],
  ['name' => 'PCB', 'colorClass' => 'bg-pcb', 'icon' => 'fa-book-open']
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Dashboard</title>
  <link
    rel="icon"
    type="image/x-icon"
    href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <div class="navbar">
    <h2><i class="fa-solid fa-layer-group"></i> MockPortal</h2>
    <div class="nav-profile" onclick="document.getElementById('student-dropdown-dash').classList.toggle('active')">
      <div class="avatar"><?= $student_initial ?></div>
      <div class="dropdown" id="student-dropdown-dash">
        <div class="dropdown-item">
          <strong style="color: #0f172a; font-size: 1rem; display: block"><?= $student_name ?></strong>
          <span style="font-size: 0.8rem">Student Account</span>
        </div>
        <button class="dropdown-btn profile-btn" onclick="window.location.href='student-profile.php'">
          <i class="fa-solid fa-user"></i> My Profile
        </button>
        <button class="dropdown-btn" onclick="window.location.href='logout.php'">
          <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </button>
      </div>
    </div>
  </div>

  <div class="container slide-up">
    <h1 class="page-title"><i class="fa-solid fa-book-open"></i> My Learning Dashboard</h1>

    <div class="grid-4">
      <?php foreach ($subjectsInfo as $info):
        $subj = $info['name'];

        $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE subject = ?");
        $stmtTotal->execute([$subj]);
        $totalVal = $stmtTotal->fetchColumn();

        $stmtAtt = $pdo->prepare("SELECT COUNT(DISTINCT h.test_id) FROM history h JOIN tests t ON h.test_id = t.id WHERE t.subject = ? AND h.student_id = ? AND h.is_deleted = FALSE");
        $stmtAtt->execute([$subj, $student_id]);
        $attemptedVal = $stmtAtt->fetchColumn();

        $notAttemptedVal = $totalVal - $attemptedVal;
      ?>
        <div class="card overview-card slide-up" onclick="window.location.href='student-subject.php?subject=<?= urlencode($subj) ?>'">
          <div class="banner <?= $info['colorClass'] ?>">
            <i class="fa-solid <?= $info['icon'] ?>"></i>
            <h3><?= $subj ?></h3>
          </div>
          <div class="overview-content">
            <div class="stats-row">
              <div class="stat">
                <div class="stat-val"><?= $totalVal ?></div>
                <div class="stat-label">Total Tests</div>
              </div>
              <div class="stat">
                <div class="stat-val" style="color: #f59e0b;"><?= $notAttemptedVal ?></div>
                <div class="stat-label">Unattempted</div>
              </div>
              <div class="stat">
                <div class="stat-val" style="color: #10b981;"><?= $attemptedVal ?></div>
                <div class="stat-label">Attempted</div>
              </div>
            </div>
            <div class="overview-btn">Explore Tests <i class="fa-solid fa-arrow-right"></i></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card mt-4 slide-up" style="padding: 32px; animation-delay: 0.2s">
      <h3 class="section-title" style="margin-bottom: 30px"><i class="fa-solid fa-chart-pie text-blue"></i> Platform Performance</h3>
      <div style="display: flex; gap: 40px; align-items: center; flex-wrap: wrap; justify-content: center;">
        <div style="flex: 1; min-width: 280px; max-width: 320px; height: 260px"><canvas id="studentOverallChart"></canvas></div>
        <div style="flex: 1; min-width: 300px">
          <div class="tcm-grid" style="font-size: 1.05rem; background: transparent; border: none; padding: 0;">
            <div style="background:#f8fafc; padding: 12px; border-radius: 8px;"><strong><i class="fa-solid fa-check-double text-blue"></i> Tests Taken</strong> <span style="font-size: 1.4rem;"><?= $tests_taken ?></span></div>
            <div style="background:#f0fdf4; padding: 12px; border-radius: 8px;"><strong><i class="fa-solid fa-circle-check text-green"></i> Total Correct</strong> <span style="font-size: 1.4rem; color:#15803d"><?= $t_correct ?></span></div>
            <div style="background:#fef2f2; padding: 12px; border-radius: 8px;"><strong><i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i> Total Wrong</strong> <span style="font-size: 1.4rem; color:#dc2626"><?= $t_wrong ?></span></div>
            <div style="background:#f1f5f9; padding: 12px; border-radius: 8px;"><strong><i class="fa-solid fa-minus text-slate"></i> Total Skipped</strong> <span style="font-size: 1.4rem; color:#64748b"><?= $t_skipped ?></span></div>
            <div style="grid-column: 1/-1; margin-top: 5px; background: linear-gradient(to right, #eff6ff, #f8fafc); padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
              <strong>Overall Accuracy Ratio</strong>
              <span style="font-size:1.8rem; font-weight: 800; color:#3b82f6;"><?= $accuracy ?>%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const ctx = document.getElementById("studentOverallChart").getContext("2d");
    const tCorrect = <?= $t_correct ?>;
    const tWrong = <?= $t_wrong ?>;
    const tSkipped = <?= $t_skipped ?>;

    if (tCorrect + tWrong + tSkipped === 0) {
      new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: ["No Data"],
          datasets: [{
            data: [1],
            backgroundColor: ["#e2e8f0"],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            tooltip: {
              enabled: false
            }
          }
        }
      });
    } else {
      new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: ["Correct", "Wrong", "Skipped"],
          datasets: [{
            data: [tCorrect, tWrong, tSkipped],
            backgroundColor: ["#10b981", "#ef4444", "#cbd5e1"],
            borderWidth: 0,
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "65%"
        }
      });
    }
  </script>
</body>

</html>