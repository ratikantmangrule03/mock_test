<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$student_name = htmlspecialchars($_SESSION['name']);
$student_initial = substr($student_name, 0, 1);
$subject = isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : '';

if (!$subject) {
    header("Location: student-dashboard.php");
    exit;
}

$icons = [
    'Physics' => 'fa-atom',
    'Chemistry' => 'fa-flask',
    'Biology' => 'fa-dna',
    'PCB' => 'fa-book-open'
];
$subjectIcon = isset($icons[$subject]) ? $icons[$subject] : 'fa-flask';

$stmtTests = $pdo->prepare("SELECT * FROM tests WHERE subject = ? ORDER BY created_at DESC");
$stmtTests->execute([$subject]);
$allTests = $stmtTests->fetchAll();

$stmtHist = $pdo->prepare("
    SELECT h.*, t.title as testTitle, t.time_limit 
    FROM history h 
    JOIN tests t ON h.test_id = t.id 
    WHERE t.subject = ? AND h.student_id = ? AND h.is_deleted = 0
    ORDER BY h.attempt_date DESC
");
$stmtHist->execute([$subject, $student_id]);
$history = $stmtHist->fetchAll();

$stmtAllAttempts = $pdo->prepare("SELECT test_id FROM history WHERE student_id = ?");
$stmtAllAttempts->execute([$student_id]);
$attempted_test_ids = $stmtAllAttempts->fetchAll(PDO::FETCH_COLUMN);

$newTests = array_filter($allTests, function ($t) use ($attempted_test_ids) {
    return !in_array($t['id'], $attempted_test_ids);
});

function formatTimePHP($seconds)
{
    $m = str_pad(floor($seconds / 60), 2, "0", STR_PAD_LEFT);
    $s = str_pad($seconds % 60, 2, "0", STR_PAD_LEFT);
    return "{$m}m {$s}s";
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title><?= $subject ?> Tests - MockPortal</title>
    <link
        rel="icon"
        type="image/x-icon"
        href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <div class="navbar">
        <h2><i class="fa-solid fa-layer-group"></i> MockPortal</h2>
        <div class="nav-profile" onclick="document.getElementById('student-dropdown-detail').classList.toggle('active')">
            <div class="avatar"><?= $student_initial ?></div>
            <div class="dropdown" id="student-dropdown-detail">
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

    <div class="container slide-up" style="max-width: 1200px">
        <div class="detail-header">
            <button class="back-btn" onclick="window.location.href='student-dashboard.php'" title="Back">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <h1 class="page-title" style="margin: 0; font-size: 2rem">
                <i class="fa-solid <?= $subjectIcon ?> text-blue"></i> <?= $subject ?>
            </h1>
        </div>

        <div style="margin-bottom: 48px">
            <h2 class="section-title">
                <i class="fa-solid fa-bolt text-blue"></i> New Tests Available
            </h2>
            <div class="test-grid">
                <?php if (count($newTests) > 0): ?>
                    <?php foreach ($newTests as $test):
                        $stmtQCount = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE test_id = ?");
                        $stmtQCount->execute([$test['id']]);
                        $qCount = $stmtQCount->fetchColumn();
                        $tMarks = $qCount * 1;
                    ?>
                        <div class="test-card-modern">
                            <div class="tcm-title"><i class="fa-regular fa-file-lines text-blue"></i> <?= htmlspecialchars($test['title']) ?></div>
                            <div class="tcm-grid">
                                <div><strong>Subject</strong> <span><i class="fa-solid fa-book fa-fw text-blue"></i> <?= htmlspecialchars($test['subject']) ?></span></div>
                                <div><strong>Questions</strong> <span><i class="fa-solid fa-list-ol fa-fw text-blue"></i> <?= $qCount ?></span></div>
                                <div><strong>Total Marks</strong> <span><i class="fa-solid fa-bullseye fa-fw text-blue"></i> <?= $tMarks ?></span></div>
                                <div><strong>Passing Marks</strong> <span><i class="fa-solid fa-check-double fa-fw text-green"></i> <?= htmlspecialchars($test['pass_marks']) ?></span></div>
                                <div><strong>Date</strong> <span><i class="fa-regular fa-calendar fa-fw text-slate"></i> <?= htmlspecialchars($test['test_date']) ?></span></div>
                                <div style="grid-column: 1 / -1; margin-top: 5px; color: #3b82f6;">
                                    <strong>Duration</strong>
                                    <span style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-stopwatch fa-fw"></i> <?= htmlspecialchars($test['time_limit']) ?> Mins</span>
                                </div>
                            </div>
                            <button class="btn-primary" style="width: 100%; margin-top: auto;" onclick="window.location.href='instructions.php?test_id=<?= $test['id'] ?>'">
                                <i class="fa-solid fa-play"></i> Attempt Now
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; padding: 24px; text-align: center; color: #94a3b8; border: 2px dashed #e2e8f0; border-radius: 12px;">
                        <i class="fa-solid fa-box-open" style="font-size:2rem; margin-bottom:10px;"></i><br>No new tests assigned.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h2 class="section-title">
                <i class="fa-solid fa-clock-rotate-left text-green"></i> Attempt History
            </h2>
            <div>
                <?php if (count($history) > 0): ?>
                    <form action="delete-history-bulk.php" method="POST" id="studentBulkDeleteForm">
                        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                            <button type="button" class="btn-danger" style="background: #dc3545; border-color: #dc3545; color: white;" onclick="submitStudentBulk('selected')">
                                <i class="fa-solid fa-trash-alt"></i> Delete Selected
                            </button>
                            <button type="button" class="btn-danger" style="background: #fd7e14; border-color: #fd7e14; color: white;" onclick="submitStudentBulk('unselected')">
                                <i class="fa-solid fa-eraser"></i> Delete Unselected
                            </button>
                            <button type="button" class="btn-danger" style="background: #6c1616; border-color: #6c1616; color: white;" onclick="submitStudentBulk('all')">
                                <i class="fa-solid fa-radiation"></i> Delete All History
                            </button>
                        </div>
                        <input type="hidden" name="delete_action" id="studentDeleteAction" value="">
                        <input type="hidden" name="subject" value="<?= $subject ?>">

                        <?php foreach ($history as $h):
                            $dateFormatted = date("M j, Y", strtotime($h['attempt_date']));
                            $displayTimeTaken = formatTimePHP($h['time_taken']);
                        ?>
                            <div class="history-list-item slide-up">
                                <div style="display: flex; align-items: center; gap: 20px;">
                                    <input type="checkbox" name="attempt_ids[]" value="<?= $h['attempt_id'] ?>" style="transform: scale(1.5);">
                                    <div class="hl-info">
                                        <h4><i class="fa-solid fa-check-double text-green"></i> <?= htmlspecialchars($h['testTitle']) ?></h4>
                                        <div class="hl-meta">
                                            <span><i class="fa-regular fa-calendar"></i> <?= $dateFormatted ?></span>
                                            <span><i class="fa-solid fa-stopwatch"></i> <?= $displayTimeTaken ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="hl-score"><i class="fa-solid fa-star"></i> Score: <?= $h['score'] ?></div>
                                <div class="hl-actions">
                                    <button type="button" class="btn-outline" style="border-color: #3b82f6; color: #3b82f6; padding: 8px 16px;" onclick="window.location.href='analysis.php?attempt_id=<?= $h['attempt_id'] ?>'" title="View Analysis">
                                        <i class="fa-solid fa-chart-pie"></i> View Report
                                    </button>
                                    <button type="button" class="btn-outline" style="border-color: #10b981; color: #10b981; padding: 8px 16px;" onclick="window.location.href='instructions.php?test_id=<?= $h['test_id'] ?>'" title="Reattempt Test">
                                        <i class="fa-solid fa-rotate-right"></i> Reattempt Test
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>
                <?php else: ?>
                    <div style="padding: 24px; text-align: center; color: #94a3b8; border: 2px dashed #e2e8f0; border-radius: 12px;">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size:2rem; margin-bottom:10px;"></i><br>No past attempts found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function submitStudentBulk(action) {
            let msg = "";
            if (action === 'selected') msg = "Hide the selected history items?";
            if (action === 'unselected') msg = "Hide all unselected history items?";
            if (action === 'all') msg = "WARNING: Hide ALL history for this subject?";

            if (confirm(msg)) {
                document.getElementById('studentDeleteAction').value = action;
                document.getElementById('studentBulkDeleteForm').submit();
            }
        }
    </script>
</body>

</html>