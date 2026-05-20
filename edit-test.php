<?php
require 'db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

// ---------------------------------------------------------
// Handle AJAX JSON POST request to bypass max_input_vars
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
  if ($contentType === "application/json") {
    $data = json_decode(file_get_contents("php://input"), true);

    try {
      $pdo->beginTransaction();

      $test_id = $data['test_id'];
      $title = trim($data['title']);
      $subject = trim($data['subject']);
      $time_limit = (int)$data['time_limit'];
      $pass_marks = (int)$data['pass_marks'];

      // Update test details
      $stmtUpdateTest = $pdo->prepare("UPDATE tests SET title = ?, subject = ?, time_limit = ?, pass_marks = ? WHERE id = ?");
      $stmtUpdateTest->execute([$title, $subject, $time_limit, $pass_marks, $test_id]);

      // Wipe old questions
      $stmtDeleteOld = $pdo->prepare("DELETE FROM questions WHERE test_id = ?");
      $stmtDeleteOld->execute([$test_id]);

      // Insert fresh question list
      if (isset($data['questions']) && is_array($data['questions'])) {
        $stmtQ = $pdo->prepare("INSERT INTO questions (test_id, question_text, option_1, option_2, option_3, option_4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($data['questions'] as $q) {
          if (empty(trim($q['text']))) continue;
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
      echo json_encode(['success' => true, 'message' => 'Test updated successfully!']);
      exit;
    } catch (Exception $e) {
      $pdo->rollBack();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
      exit;
    }
  }
}

// ---------------------------------------------------------
// Standard GET Request to Load Page UI
// ---------------------------------------------------------
$admin_initial = substr($_SESSION['name'], 0, 1);
$test_id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '';

if (!$test_id) {
  header("Location: admin-dashboard.php");
  exit;
}

$stmtTest = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
$stmtTest->execute([$test_id]);
$test = $stmtTest->fetch();
if (!$test) die("Test not found.");

$stmtQuestions = $pdo->prepare("SELECT * FROM questions WHERE test_id = ?");
$stmtQuestions->execute([$test_id]);
$questions = $stmtQuestions->fetchAll();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Test - Admin</title>
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
      <div class="avatar"><?= $admin_initial ?></div>
    </div>
  </div>

  <div class="container slide-up">
    <div class="detail-header">
      <button class="back-btn" onclick="window.location.href='admin-subject.php?subject=<?= urlencode($test['subject']) ?>'"><i class="fa-solid fa-arrow-left"></i></button>
      <h1 class="page-title" style="margin-bottom: 0;">Edit Test: <span id="displayTitle"><?= htmlspecialchars($test['title']) ?></span></h1>
    </div>

    <div id="successAlert" class="alert alert-success" style="display: none;"><i class="fa-solid fa-check"></i> Test updated successfully!</div>
    <div id="errorAlert" class="alert alert-error" style="display: none;"><i class="fa-solid fa-triangle-exclamation"></i> <span id="errorText"></span></div>

    <form id="editTestForm" class="card mt-4" style="padding: 30px;">
      <input type="hidden" name="test_id" value="<?= htmlspecialchars($test_id) ?>">

      <h3 class="section-title">Test Details</h3>
      <div class="grid-4" style="margin-bottom: 30px;">
        <div>
          <label class="form-label">Test Title</label>
          <input type="text" name="title" required value="<?= htmlspecialchars($test['title']) ?>">
        </div>
        <div>
          <label class="form-label">Subject</label>
          <select name="subject" required>
            <option value="Physics" <?= $test['subject'] === 'Physics' ? 'selected' : '' ?>>Physics</option>
            <option value="Chemistry" <?= $test['subject'] === 'Chemistry' ? 'selected' : '' ?>>Chemistry</option>
            <option value="Biology" <?= $test['subject'] === 'Biology' ? 'selected' : '' ?>>Biology</option>
            <option value="PCB" <?= $test['subject'] === 'PCB' ? 'selected' : '' ?>>PCB</option>
          </select>
        </div>
        <div>
          <label class="form-label">Time Limit (Minutes)</label>
          <input type="number" name="time_limit" required min="1" value="<?= htmlspecialchars($test['time_limit']) ?>">
        </div>
        <div>
          <label class="form-label">Passing Marks</label>
          <input type="number" name="pass_marks" required min="1" value="<?= htmlspecialchars($test['pass_marks']) ?>">
        </div>
      </div>

      <h3 class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
        Questions
        <div style="display: flex; gap: 12px; align-items: center;">
          <button type="submit" id="saveBtn" class="btn-primary" style="display: none; padding: 10px 20px;">
            <i class="fa-solid fa-floppy-disk"></i> Save Changes
          </button>
          <button type="button" class="btn-outline" onclick="addQuestion()" style="padding: 10px 20px;">
            <i class="fa-solid fa-plus"></i> Add Question
          </button>
        </div>
      </h3>

      <div id="questions-container">
        <?php foreach ($questions as $index => $q): ?>
          <div class="card q-block-admin" id="q-block-<?= $index ?>" style="padding: 40px; background: #f8fafc; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; position: relative;">
            <button type="button" onclick="removeQuestion(<?= $index ?>)" style="position: absolute; top: 15px; right: 15px; background: #fee2e2; color: #dc2626; border: none; width: 35px; height: 45px; border-radius: 8px; cursor: pointer;">
              <i class="fa-solid fa-trash"></i>
            </button>
            <label class="form-label">Question</label>
            <input type="text" name="questions[<?= $index ?>][text]" required value="<?= htmlspecialchars($q['question_text']) ?>" style="margin-bottom: 15px;">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
              <input type="text" name="questions[<?= $index ?>][opt1]" required value="<?= htmlspecialchars($q['option_1']) ?>" placeholder="Option 1">
              <input type="text" name="questions[<?= $index ?>][opt2]" required value="<?= htmlspecialchars($q['option_2']) ?>" placeholder="Option 2">
              <input type="text" name="questions[<?= $index ?>][opt3]" required value="<?= htmlspecialchars($q['option_3']) ?>" placeholder="Option 3">
              <input type="text" name="questions[<?= $index ?>][opt4]" required value="<?= htmlspecialchars($q['option_4']) ?>" placeholder="Option 4">
            </div>
            <label class="form-label">Correct Option Number (1-4)</label>
            <input type="number" name="questions[<?= $index ?>][correct]" required min="1" max="4" value="<?= htmlspecialchars($q['correct_option']) ?>" style="width: 150px;">
          </div>
        <?php endforeach; ?>
      </div>
    </form>
  </div>

  <script>
    let qCount = <?= count($questions) ?>;

    // Listen for inputs to display Save button inline
    document.addEventListener("DOMContentLoaded", () => {
      const saveBtn = document.getElementById('saveBtn');
      document.body.addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
          saveBtn.style.display = 'inline-flex';
        }
      });
    });

    function addQuestion() {
      const container = document.getElementById('questions-container');
      const html = `
        <div class="card q-block-admin" id="q-block-${qCount}" style="padding: 20px; background: #f8fafc; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; position: relative;">
          <button type="button" onclick="removeQuestion(${qCount})" style="position: absolute; top: 15px; right: 15px; background: #fee2e2; color: #dc2626; border: none; width: 35px; height: 35px; border-radius: 8px; cursor: pointer;">
            <i class="fa-solid fa-trash"></i>
          </button>
          <label class="form-label">New Question</label>
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
      document.getElementById('saveBtn').style.display = 'inline-flex';
    }

    function removeQuestion(id) {
      const block = document.getElementById(`q-block-${id}`);
      if (block) {
        block.remove();
        document.getElementById('saveBtn').style.display = 'inline-flex';
      }
    }

    // AJAX Form Submission Logic
    document.getElementById('editTestForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const saveBtn = document.getElementById('saveBtn');
      const originalBtnText = saveBtn.innerHTML;
      saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
      saveBtn.disabled = true;

      const formData = new FormData(this);

      // Build base payload
      const payload = {
        test_id: formData.get('test_id'),
        title: formData.get('title'),
        subject: formData.get('subject'),
        time_limit: formData.get('time_limit'),
        pass_marks: formData.get('pass_marks'),
        questions: []
      };

      // Traverse all question blocks to scrape inputs safely
      const qBlocks = document.querySelectorAll('.q-block-admin');
      qBlocks.forEach(block => {
        const inputs = block.querySelectorAll('input');
        let qData = {
          text: '',
          opt1: '',
          opt2: '',
          opt3: '',
          opt4: '',
          correct: ''
        };

        inputs.forEach(input => {
          if (input.name.includes('[text]')) qData.text = input.value;
          if (input.name.includes('[opt1]')) qData.opt1 = input.value;
          if (input.name.includes('[opt2]')) qData.opt2 = input.value;
          if (input.name.includes('[opt3]')) qData.opt3 = input.value;
          if (input.name.includes('[opt4]')) qData.opt4 = input.value;
          if (input.name.includes('[correct]')) qData.correct = input.value;
        });

        // Only push valid questions
        if (qData.text.trim() !== '') {
          payload.questions.push(qData);
        }
      });

      // Send payload as JSON
      fetch('edit-test.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.getElementById('successAlert').style.display = 'flex';
            document.getElementById('errorAlert').style.display = 'none';
            document.getElementById('displayTitle').innerText = payload.title;

            // Hide save button again after successful save
            setTimeout(() => {
              document.getElementById('successAlert').style.display = 'none';
              saveBtn.style.display = 'none';
              saveBtn.innerHTML = originalBtnText;
              saveBtn.disabled = false;
            }, 2000);
          } else {
            throw new Error(data.message || 'Unknown error occurred.');
          }
        })
        .catch(err => {
          document.getElementById('errorText').innerText = err.message;
          document.getElementById('errorAlert').style.display = 'flex';
          document.getElementById('successAlert').style.display = 'none';

          saveBtn.innerHTML = originalBtnText;
          saveBtn.disabled = false;
        });
    });
  </script>
</body>

</html>