<?php
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$admin_name = htmlspecialchars($_SESSION['name']);
$admin_initial = substr($admin_name, 0, 1);

$total_tests = $pdo->query("SELECT COUNT(*) FROM tests")->fetchColumn();
$total_attempts = $pdo->query("SELECT COUNT(*) FROM history")->fetchColumn();

$subjectsInfo = [
  ['name' => 'Physics', 'colorClass' => 'bg-physics', 'icon' => 'fa-atom'],
  ['name' => 'Chemistry', 'colorClass' => 'bg-chemistry', 'icon' => 'fa-flask'],
  ['name' => 'Biology', 'colorClass' => 'bg-biology', 'icon' => 'fa-dna'],
  ['name' => 'PCB', 'colorClass' => 'bg-pcb', 'icon' => 'fa-book-open']
];

$chartLabels = [];
$chartData = [];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
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
    <h2><i class="fa-solid fa-shield-halved"></i> Portal Admin</h2>
    <div class="nav-profile" onclick="document.getElementById('admin-dropdown-dash').classList.toggle('active')">
      <div class="avatar" style="background: linear-gradient(135deg, #0f172a 0%, #334155 100%);"><?= $admin_initial ?></div>
      <div class="dropdown" id="admin-dropdown-dash">
        <div class="dropdown-item">
          <strong style="color: #0f172a; font-size: 1rem; display: block"><?= $admin_name ?></strong>
          <span style="font-size: 0.8rem">Administrator</span>
        </div>
        <button class="dropdown-btn" onclick="window.location.href='logout.php'">
          <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </button>
      </div>
    </div>
  </div>

  <div class="container slide-up">
    <h1 class="page-title"><i class="fa-solid fa-chalkboard-user"></i> Manage Subjects</h1>
    <div class="grid-4">
      <?php foreach ($subjectsInfo as $info):
        $subj = $info['name'];

        $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE subject = ?");
        $stmtTotal->execute([$subj]);
        $totalVal = $stmtTotal->fetchColumn();

        $stmtAtt = $pdo->prepare("SELECT COUNT(*) FROM history WHERE test_id IN (SELECT id FROM tests WHERE subject = ?)");
        $stmtAtt->execute([$subj]);
        $attemptedVal = $stmtAtt->fetchColumn();

        // FIX: Replaced raw string inject with a prepared statement
        $stmtUnique = $pdo->prepare("SELECT COUNT(DISTINCT test_id) FROM history WHERE test_id IN (SELECT id FROM tests WHERE subject = ?)");
        $stmtUnique->execute([$subj]);
        $uniqueAttempted = $stmtUnique->fetchColumn();

        $notAttemptedVal = $totalVal > 0 ? $totalVal - $uniqueAttempted : 0;

        $chartLabels[] = $subj;
        $chartData[] = $attemptedVal;
      ?>
        <div class="card overview-card slide-up" onclick="window.location.href='admin-subject.php?subject=<?= urlencode($subj) ?>'">
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
                <div class="stat-label">Total Attempts</div>
              </div>
            </div>
            <div class="overview-btn">Manage Subject <i class="fa-solid fa-gear"></i></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card mt-4 slide-up" style="padding: 32px; animation-delay: 0.2s">
      <h3 class="section-title" style="margin-bottom: 30px"><i class="fa-solid fa-chart-line text-blue"></i> Global Platform Insights</h3>
      <div style="display: flex; gap: 40px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px; max-width: 600px; height: 260px"><canvas id="adminOverallChart"></canvas></div>
        <div style="flex: 1; min-width: 300px">
          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
            <div style="margin-bottom: 20px;">
              <span style="display:block; color:#64748b; font-weight: 600; text-transform: uppercase; font-size: 0.85rem;"><i class="fa-solid fa-layer-group"></i> Total Tests Hosted</span>
              <span style="font-size: 2.5rem; font-weight: 700; color: #0f172a; line-height: 1.1;"><?= $total_tests ?></span>
            </div>
            <div>
              <span style="display:block; color:#64748b; font-weight: 600; text-transform: uppercase; font-size: 0.85rem;"><i class="fa-solid fa-users"></i> Total Global Attempts</span>
              <span style="font-size: 2.5rem; font-weight: 700; color: #3b82f6; line-height: 1.1;"><?= $total_attempts ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const ctx = document.getElementById("adminOverallChart").getContext("2d");
    new Chart(ctx, {
      type: "bar",
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          label: "Student Attempts per Subject",
          data: <?= json_encode($chartData) ?>,
          backgroundColor: "#3b82f6",
          borderRadius: 6,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              display: false
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        },
      },
    });
  </script>
</body>

</html>