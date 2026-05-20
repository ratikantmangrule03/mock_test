<?php
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$admin_initial = substr($_SESSION['name'], 0, 1);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $pdo->beginTransaction();

    // 1. Create the Test
    $test_id = uniqid('test_');
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $time_limit = (int)$_POST['time_limit'];
    $pass_marks = (int)$_POST['pass_marks'];
    $test_date = date('Y-m-d');

    $stmtTest = $pdo->prepare("INSERT INTO tests (id, title, subject, time_limit, pass_marks, test_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtTest->execute([$test_id, $title, $subject, $time_limit, $pass_marks, $test_date]);

    // 2. Add the Questions
    if (isset($_POST['questions']) && is_array($_POST['questions'])) {
      $stmtQ = $pdo->prepare("INSERT INTO questions (test_id, question_text, option_1, option_2, option_3, option_4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");

      foreach ($_POST['questions'] as $q) {
        $stmtQ->execute([
          $test_id,
          trim($q['text']),
          trim($q['opt1']),
          trim($q['opt2']),
          trim($q['opt3']),
          trim($q['opt4']),
          (int)$q['correct']
        ]);
      }
    }

    $pdo->commit();
    $success = "Test created successfully!";
  } catch (Exception $e) {
    $pdo->rollBack();
    $error = "Failed to create test: " . $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Create Test - Admin</title>
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
    <h2><i class="fa-solid fa-shield-halved"></i> Portal Admin</h2>
    <div class="nav-profile">
      <div class="avatar" style="background: linear-gradient(135deg, #0f172a 0%, #334155 100%);"><?= $admin_initial ?></div>
    </div>
  </div>

  <div class="container slide-up">
    <div class="detail-header">
      <button class="back-btn" onclick="window.location.href='admin-dashboard.php'"><i class="fa-solid fa-arrow-left"></i></button>
      <h1 class="page-title" style="margin-bottom: 0;">Create New Test</h1>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="create-test.php" class="card mt-4" style="padding: 30px;">
      <h3 class="section-title">Test Details</h3>
      <div class="grid-4" style="margin-bottom: 30px;">
        <div>
          <label class="form-label">Test Title</label>
          <input type="text" name="title" required placeholder="e.g. Thermodynamics Quiz 1">
        </div>
        <div>
          <label class="form-label">Subject</label>
          <select name="subject" required>
            <option value="Physics">Physics</option>
            <option value="Chemistry">Chemistry</option>
            <option value="Biology">Biology</option>
            <option value="PCB">PCB</option>
          </select>
        </div>
        <div>
          <label class="form-label">Time Limit (Minutes)</label>
          <input type="number" name="time_limit" required min="1" value="30">
        </div>
        <div>
          <label class="form-label">Passing Marks</label>
          <input type="number" name="pass_marks" required min="1" value="10">
        </div>
      </div>

      <h3 class="section-title" style="display: flex; justify-content: space-between;">
        Questions
        <button type="button" class="btn-outline" onclick="addQuestion()"><i class="fa-solid fa-plus"></i> Add Question</button>
      </h3>

      <div id="questions-container">
      </div>

      <button type="submit" class="btn-primary mt-4" style="width: 100%; font-size: 1.1rem; padding: 15px;">
        <i class="fa-solid fa-floppy-disk"></i> Save Entire Test
      </button>
    </form>
  </div>

  <script>
    let qCount = 0;

    function addQuestion() {
      const container = document.getElementById('questions-container');
      const html = `
        <div class="card" style="padding: 20px; background: #f8fafc; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <label class="form-label">Question ${qCount + 1}</label>
          <input type="text" name="questions[${qCount}][text]" required placeholder="Enter question text here..." style="margin-bottom: 15px;">
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <input type="text" name="questions[${qCount}][opt1]" required placeholder="Option 1">
            <input type="text" name="questions[${qCount}][opt2]" required placeholder="Option 2">
            <input type="text" name="questions[${qCount}][opt3]" required placeholder="Option 3">
            <input type="text" name="questions[${qCount}][opt4]" required placeholder="Option 4">
          </div>
          
          <label class="form-label">Correct Option Number (1-4)</label>
          <input type="number" name="questions[${qCount}][correct]" required min="1" max="4" placeholder="e.g. 2" style="width: 150px;">
        </div>
      `;
      container.insertAdjacentHTML('beforeend', html);
      qCount++;
    }

    // Add one question by default
    document.addEventListener("DOMContentLoaded", addQuestion);
  </script>
</body>

</html>