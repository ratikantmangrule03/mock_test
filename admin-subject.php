<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
 header("Location: login.php");
 exit;
}

$admin_name = htmlspecialchars($_SESSION['name']);
$admin_initial = substr($admin_name, 0, 1);
$subject = isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : '';

if (!$subject) {
 header("Location: admin-dashboard.php");
 exit;
}

$stmt = $pdo->prepare("SELECT * FROM tests WHERE subject = ? ORDER BY created_at DESC");
$stmt->execute([$subject]);
$tests = $stmt->fetchAll();

$stmtHistAdmin = $pdo->prepare("
    SELECT h.*, t.title as testTitle, u.name as studentName 
    FROM history h 
    JOIN tests t ON h.test_id = t.id 
    JOIN users u ON h.student_id = u.id
    WHERE t.subject = ? 
    ORDER BY h.attempt_date DESC
");
$stmtHistAdmin->execute([$subject]);
$adminHistory = $stmtHistAdmin->fetchAll();

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
 <title>Manage <?= $subject ?> - Admin</title>
 <link
  rel="icon"
  type="image/x-icon"
  href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
 <link rel="stylesheet" href="style.css" />
 <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.21/mammoth.browser.min.js"></script>
 <script>
  pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";
 </script>
</head>

<body>
 <div class="navbar">
  <h2><i class="fa-solid fa-shield-halved"></i> Portal Admin</h2>
  <div class="nav-profile" onclick="toggleDropdown('admin-dropdown-dash')">
   <div class="avatar"><?= $admin_initial ?></div>
   <div class="dropdown" id="admin-dropdown-dash">
    <button class="dropdown-btn" onclick="window.location.href='logout.php'"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</button>
   </div>
  </div>
 </div>

 <div class="container slide-up" style="max-width: 1200px">
  <div class="detail-header">
   <button class="back-btn" onclick="window.location.href='admin-dashboard.php'"><i class="fa-solid fa-arrow-left"></i></button>
   <h1 class="page-title" style="margin: 0;">Manage <?= $subject ?></h1>
  </div>

  <div class="card" style="padding: 40px; margin-bottom: 40px">
   <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 10px"><i class="fa-solid fa-cloud-arrow-up text-blue"></i> Upload New Test</h2>
   <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; margin-top: 20px;">
    <div style="grid-column: 1 / -1;">
     <label class="form-label">Test Name (Optional)</label>
     <input type="text" id="importTestName" placeholder="Leaves blank to use filename" />
    </div>
    <div>
     <label class="form-label">Time Limit (Mins)</label>
     <input type="number" id="importTimeLimit" value="30" min="1" />
    </div>
    <div>
     <label class="form-label">Passing Marks</label>
     <input type="number" id="importPassMarks" value="10" min="0" />
    </div>
    <div>
     <label class="form-label">Scheduled Date</label>
     <input type="date" id="importDate" value="<?= date('Y-m-d') ?>" />
    </div>
   </div>
   <label for="fileInput" style="display: block">
    <div class="upload-zone">
     <i class="fa-solid fa-file-arrow-up"></i>
     <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 8px;">Click to browse for a document</div>
     <div style="font-size: 0.85rem; color: #64748b">Supports PDF, DOCX, TXT, CSV.</div>
    </div>
   </label>
   <input type="file" id="fileInput" accept=".csv, .txt, .pdf, .docx" style="display: none" onchange="handleFileUpload(event)" />
   <div id="uploadStatus" style="display: none; margin-top: 20px;"></div>
  </div>

  <div class="test-grid">
   <?php foreach ($tests as $test):
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
      <div><strong>Pass Marks</strong> <span><i class="fa-solid fa-check-double fa-fw text-green"></i> <?= htmlspecialchars($test['pass_marks']) ?></span></div>
      <div><strong>Date</strong> <span><i class="fa-regular fa-calendar fa-fw text-slate"></i> <?= htmlspecialchars($test['test_date']) ?></span></div>
      <div style="grid-column: 1 / -1; margin-top: 5px; color: #3b82f6;">
       <strong>Duration</strong>
       <span style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-stopwatch fa-fw"></i> <?= htmlspecialchars($test['time_limit']) ?> Mins</span>
      </div>
     </div>
     <div style="display: flex; gap: 10px; margin-top: auto;">
      <button class="btn-outline" style="flex: 1;" onclick="window.location.href='edit-test.php?id=<?= $test['id'] ?>'"><i class="fa-solid fa-pen"></i> Edit Details</button>
      <form action="delete-test.php" method="POST" style="margin: 0; display: flex; flex: 1;" onsubmit="return confirm('Are you sure you want to completely delete this test and all its history?');">
       <input type="hidden" name="test_id" value="<?= $test['id'] ?>">
       <button type="submit" class="btn-danger" style="width: 100%; border-radius: 10px;"><i class="fa-solid fa-trash"></i> Delete</button>
      </form>
     </div>
    </div>
   <?php endforeach; ?>
  </div>

  <div style="margin-top: 48px;">
   <h2 class="section-title">
    <i class="fa-solid fa-clock-rotate-left text-green"></i> Global Attempt History
   </h2>
   <div>
    <?php if (count($adminHistory) > 0): ?>
     <form action="delete-history-bulk.php" method="POST" id="adminBulkDeleteForm">
      <div style="display: flex; gap: 15px; margin-bottom: 20px;">
       <button type="button" class="btn-danger" style="background: #dc3545; border-color: #dc3545; color: white;" onclick="submitAdminBulk('selected')">
        <i class="fa-solid fa-trash-alt"></i> Delete Selected
       </button>
       <button type="button" class="btn-danger" style="background: #fd7e14; border-color: #fd7e14; color: white;" onclick="submitAdminBulk('unselected')">
        <i class="fa-solid fa-eraser"></i> Delete Unselected
       </button>
       <button type="button" class="btn-danger" style="background: #6c1616; border-color: #6c1616; color: white;" onclick="submitAdminBulk('all')">
        <i class="fa-solid fa-radiation"></i> Delete All Data
       </button>
      </div>
      <input type="hidden" name="delete_action" id="adminDeleteAction" value="">
      <input type="hidden" name="subject" value="<?= $subject ?>">

      <?php foreach ($adminHistory as $h):
       $dateFormatted = date("M j, Y, g:i A", strtotime($h['attempt_date']));
       $displayTimeTaken = formatTimePHP($h['time_taken']);
      ?>
       <div class="history-list-item slide-up">
        <div style="display: flex; align-items: center; gap: 20px;">
         <input type="checkbox" name="attempt_ids[]" value="<?= $h['attempt_id'] ?>" style="transform: scale(1.5);">
         <div class="hl-info">
          <h4><i class="fa-solid fa-user-graduate text-blue"></i> <?= htmlspecialchars($h['studentName']) ?></h4>
          <div class="hl-meta">
           <span><i class="fa-solid fa-check-double text-green"></i> <?= htmlspecialchars($h['testTitle']) ?></span>
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
        </div>
       </div>
      <?php endforeach; ?>
     </form>
    <?php else: ?>
     <div style="padding: 24px; text-align: center; color: #94a3b8; border: 2px dashed #e2e8f0; border-radius: 12px;">
      <i class="fa-solid fa-clock-rotate-left" style="font-size:2rem; margin-bottom:10px;"></i><br>No student attempts recorded yet for this subject.
     </div>
    <?php endif; ?>
   </div>
  </div>
 </div>

 <script src="shared.js"></script>
 <script>
  function submitAdminBulk(action) {
   let msg = "";
   if (action === 'selected') msg = "Permanently delete the selected history items?";
   if (action === 'unselected') msg = "Permanently delete all unselected history items?";
   if (action === 'all') msg = "WARNING: Permanently wipe ALL global history for this subject?";

   if (confirm(msg)) {
    document.getElementById('adminDeleteAction').value = action;
    document.getElementById('adminBulkDeleteForm').submit();
   }
  }

  const currentSubjectContext = "<?= $subject ?>";
  // ... Keep all your existing upload/parsing functions identical ...
  async function handleFileUpload(event) {
   const file = event.target.files[0];
   let testName = document.getElementById("importTestName").value.trim() || file.name.replace(/\.[^/.]+$/, "");
   const timeLimit = parseInt(document.getElementById("importTimeLimit").value) || 30;
   const passMarks = parseInt(document.getElementById("importPassMarks").value) || 10;
   const testDate = document.getElementById("importDate").value;
   const statusDiv = document.getElementById("uploadStatus");
   if (!file) return;
   statusDiv.style.display = "flex";
   statusDiv.className = "alert alert-info";
   statusDiv.innerHTML = `<i class='fa-solid fa-spinner fa-spin'></i> <div><strong>Processing Document</strong><br>Extracting questions...</div>`;
   const ext = file.name.split(".").pop().toLowerCase();
   let extractedText = "";
   try {
    if (ext === "txt" || ext === "csv") extractedText = await readFileAsText(file);
    else if (ext === "docx") extractedText = await readWordDoc(file);
    else if (ext === "pdf") extractedText = await readPDF(file);
    else throw new Error("Unsupported format.");
    parseAndSaveQuestions(extractedText, testName, timeLimit, passMarks, testDate, currentSubjectContext, statusDiv, ext);
   } catch (error) {
    statusDiv.className = "alert alert-error";
    statusDiv.innerHTML = `<div><strong>Extraction Failed</strong><br>${error.message}</div>`;
   }
   event.target.value = "";
  }

  function readFileAsText(file) {
   return new Promise(res => {
    const r = new FileReader();
    r.onload = e => res(e.target.result);
    r.readAsText(file);
   });
  }

  function readWordDoc(file) {
   return new Promise((res, rej) => {
    const r = new FileReader();
    r.onload = e => {
     mammoth.extractRawText({
      arrayBuffer: e.target.result
     }).then(r => res(r.value)).catch(rej);
    };
    r.readAsArrayBuffer(file);
   });
  }
  async function readPDF(file) {
   const pdf = await pdfjsLib.getDocument({
    data: await file.arrayBuffer()
   }).promise;
   let text = "";
   for (let i = 1; i <= pdf.numPages; i++) text += (await (await pdf.getPage(i)).getTextContent()).items.map(item => item.str).join(" ") + "\\n";
   return text;
  }
  async function parseAndSaveQuestions(text, testName, timeLimit, passMarks, testDate, subject, statusDiv, ext) {
   const questions = [];
   if (ext === "csv") {
    const rows = text.split(/\r?\n/).map(row => row.split(/,(?=(?:(?:[^"]*"){2})*[^"]*$)/));
    for (let i = 1; i < rows.length; i++) {
     const row = rows[i];
     if (row.length >= 6) {
      questions.push({
       q: row[0].replace(/^"|"$/g, "").trim(),
       opts: [row[1], row[2], row[3], row[4]].map(o => o.replace(/^"|"$/g, "").trim()),
       ans: parseInt(row[5].trim())
      });
     }
    }
   } else {
    const lines = text.split(/\r?\n/).map(l => l.trim().replace(/\s+/g, " ")).filter(l => l.length > 0);
    let currentQ = null;
    for (let line of lines) {
     if (/^(?:Q|Question)?\s*\d+[\.\):\s]\s*(.*)/i.test(line)) {
      if (currentQ && currentQ.opts.length >= 2 && currentQ.ans !== null) questions.push(currentQ);
      currentQ = {
       q: line.replace(/^(?:Q|Question)?\s*\d+[\.\):\s]\s*/i, ""),
       opts: [],
       ans: null
      };
     } else if (/^[a-d1-4][\.\)]\s*(.*)/i.test(line) && currentQ) {
      currentQ.opts.push(line.replace(/^[a-d1-4][\.\)]\s*/i, ""));
     } else if (/^(?:Ans|Answer|Correct(?: Option)?)[^A-Za-z0-9]*([A-D1-4]|.*)/i.test(line) && currentQ) {
      let match = line.match(/^(?:Ans|Answer|Correct(?: Option)?)[^A-Za-z0-9]*([A-D1-4]|.*)/i);
      if (match) {
       let val = match[1].trim().toUpperCase();
       if (["A", "B", "C", "D"].includes(val)) currentQ.ans = val.charCodeAt(0) - 65;
       else if (["1", "2", "3", "4"].includes(val)) currentQ.ans = parseInt(val) - 1;
       else currentQ.ans = currentQ.opts.findIndex(o => o.toUpperCase().includes(val));
      }
     }
    }
    if (currentQ && currentQ.opts.length >= 2 && currentQ.ans !== null) questions.push(currentQ);
   }
   const validQs = questions.filter(q => q.ans !== null && q.opts.length >= 2);
   if (validQs.length === 0) {
    statusDiv.className = "alert alert-error";
    statusDiv.innerHTML = "Could not find questions. Ensure text uses 'Q1.' and 'Answer: c' or CSV has headers.";
    return;
   }
   const payload = {
    title: testName,
    subject: subject,
    timeLimit,
    passMarks,
    testDate,
    questions: validQs
   };
   try {
    const response = await fetch('ajax-upload.php', {
     method: 'POST',
     headers: {
      'Content-Type': 'application/json'
     },
     body: JSON.stringify(payload)
    });
    if (!response.ok) throw new Error("Server Error");
    statusDiv.className = "alert alert-success";
    statusDiv.innerHTML = `<i class='fa-solid fa-circle-check'></i> <div><strong>Success</strong><br>Imported ${validQs.length} questions.</div>`;
    setTimeout(() => window.location.reload(), 1500);
   } catch (e) {
    statusDiv.className = "alert alert-error";
    statusDiv.innerHTML = `<div><strong>Upload Failed</strong><br>${e.message}</div>`;
   }
  }
 </script>
</body>

</html>