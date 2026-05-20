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

$stmtTest = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
$stmtTest->execute([$test_id]);
$test = $stmtTest->fetch();

$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE test_id = ?");
$stmtQ->execute([$test_id]);
$questions = $stmtQ->fetchAll();

// Encode questions to JSON for the JavaScript engine to use
$jsonQuestions = json_encode($questions);
?>
<!doctype html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <title>Exam - <?= htmlspecialchars($test['title']) ?></title>
   <link
      rel="icon"
      type="image/x-icon"
      href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
   <link rel="stylesheet" href="style.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>

<body style="overflow: hidden;">
   <form id="examForm" method="POST" action="result.php" style="display:none;">
      <input type="hidden" name="test_id" value="<?= $test['id'] ?>">
      <input type="hidden" name="time_taken" id="formTimeTaken" value="0">
      <div id="hiddenInputsContainer"></div>
   </form>

   <div id="exam-view" style="height: 100vh; display: flex; flex-direction: column;">
      <header style="background: white; padding: 12px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; height: 65px;">
         <div style="font-weight: 700; font-size: 1.25rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-pen-to-square text-blue"></i> <?= htmlspecialchars($test['title']) ?>
         </div>
         <div id="timerDisplay" style="background: #eff6ff; color: #1d4ed8; padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: 1.05rem; border: 1px solid #bfdbfe;">
            <i class="fa-regular fa-clock fa-fade"></i> --:--
         </div>
      </header>

      <div class="exam-main" style="display: flex; flex: 1; padding: 20px; gap: 20px; max-width: 1400px; margin: 0 auto; width: 100%; height: calc(100vh - 65px); box-sizing: border-box;">
         <div class="exam-left card" style="flex: 1; overflow-y: auto; padding: 30px; display: flex; flex-direction: column;">
            <div class="q-header" style="display: flex; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9;">
               <span id="qno" style="font-size: 1.1rem; font-weight: 700;">Question 1</span>
               <span style="color: #ef4444; background: #fef2f2; padding: 4px 12px; border-radius: 6px; font-weight: 700;">+1 / -5</span>
            </div>

            <div id="questionText" class="question-text" style="font-size: 1.15rem; font-weight: 500; margin-bottom: 25px;"></div>
            <div id="optionsContainer" style="display: flex; flex-direction: column; gap: 12px;"></div>

            <div style="display: flex; justify-content: space-between; margin-top: auto; border-top: 2px solid #f1f5f9; padding-top: 20px;">
               <button type="button" class="btn-outline" onclick="prevQuestion()"><i class="fa-solid fa-arrow-left"></i> Previous</button>
               <div style="display: flex; gap: 12px">
                  <button type="button" class="btn-warning" onclick="markForReview()"><i class="fa-solid fa-bookmark"></i> Review Question</button>
                  <button type="button" class="btn-primary" onclick="nextQuestion()">Save & Next <i class="fa-solid fa-arrow-right"></i></button>
               </div>
            </div>
         </div>

         <div class="exam-sidebar card" style="width: 360px; display: flex; flex-direction: column; padding: 30px; overflow-y: auto;">
            <h3 style="margin-bottom: 24px; font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-grip"></i> Question Palette</h3>
            <div class="palette" id="palette" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(48px, 1fr)); gap: 10px; flex: 1; align-content: start;"></div>
            <button type="button" class="btn-primary btn-block" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); margin-top: auto; padding: 16px;" onclick="submitExam()">
               <i class="fa-solid fa-check-double"></i> Final Submit
            </button>
         </div>
      </div>
   </div>

   <script>
      const questions = <?= $jsonQuestions ?>;
      const timeLimitMins = <?= $test['time_limit'] ?>;

      let currentIdx = 0;
      let answers = new Array(questions.length).fill(null);
      let reviewMarked = new Array(questions.length).fill(false);
      let totalSeconds = timeLimitMins * 60;
      let timeTaken = 0;
      let examSecurityActive = true;

      // Timer Logic
      const timerDisplay = document.getElementById('timerDisplay');
      const interval = setInterval(() => {
         totalSeconds--;
         timeTaken++;
         let m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
         let s = (totalSeconds % 60).toString().padStart(2, '0');
         timerDisplay.innerHTML = `<i class="fa-regular fa-clock fa-fade"></i> ${m}:${s}`;

         if (totalSeconds < 300) {
            timerDisplay.style.background = "#fef2f2";
            timerDisplay.style.color = "#dc2626";
         }
         if (totalSeconds <= 0) {
            clearInterval(interval);
            alert("Time is up! Submitting auto...");
            submitExam();
         }
      }, 1000);

      // Anti-Cheat
      document.addEventListener("visibilitychange", function() {
         if (document.hidden && examSecurityActive) {
            alert("SECURITY WARNING: You switched tabs! Exam submitted.");
            submitExam();
         }
      });

      // Rendering Logic
      function renderQuestion() {
         document.getElementById('qno').innerText = `Question ${currentIdx + 1} of ${questions.length}`;
         document.getElementById('questionText').innerText = questions[currentIdx].question_text;

         const opts = [questions[currentIdx].option_1, questions[currentIdx].option_2, questions[currentIdx].option_3, questions[currentIdx].option_4];
         let html = '';
         opts.forEach((opt, idx) => {
            if (!opt) return;
            const isSelected = answers[currentIdx] === (idx + 1);
            html += `<label class="option ${isSelected ? 'selected' : ''}" onclick="selectOpt(${idx + 1})" style="padding: 12px 20px; margin-bottom: 12px; font-size: 1rem;">
                <input type="radio" name="ans_opt" style="margin-right: 12px; transform: scale(1.2);" ${isSelected ? 'checked' : ''}> ${opt}
             </label>`;
         });
         document.getElementById('optionsContainer').innerHTML = html;
         renderPalette();
      }

      function selectOpt(val) {
         answers[currentIdx] = val;
         renderQuestion();
      }

      function prevQuestion() {
         if (currentIdx > 0) {
            currentIdx--;
            renderQuestion();
         }
      }

      function nextQuestion() {
         if (currentIdx < questions.length - 1) currentIdx++;
         renderQuestion();
      }

      function markForReview() {
         reviewMarked[currentIdx] = !reviewMarked[currentIdx];
         renderPalette();
      }

      function jumpTo(idx) {
         currentIdx = idx;
         renderQuestion();
      }

      function renderPalette() {
         let html = '';
         for (let i = 0; i < questions.length; i++) {
            let classes = ['btn'];
            if (i === currentIdx) classes.push('active');
            if (reviewMarked[i]) classes.push('review');
            else if (answers[i] !== null) classes.push('answered');

            html += `<button type="button" class="${classes.join(' ')}" onclick="jumpTo(${i})" style="height: 48px; border: 2px solid #e2e8f0; border-radius: 10px; font-weight: 600; cursor:pointer; background: ${classes.includes('review') ? '#f59e0b' : classes.includes('answered') ? '#10b981' : 'white'}; color: ${classes.includes('review') || classes.includes('answered') ? 'white' : '#475569'}; ${classes.includes('active') ? 'border-color:#3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.3);' : ''}">${i + 1}</button>`;
         }
         document.getElementById('palette').innerHTML = html;
      }

      function submitExam() {
         examSecurityActive = false;
         clearInterval(interval);
         document.getElementById('formTimeTaken').value = timeTaken;

         let inputs = '';
         answers.forEach((ans, i) => {
            if (ans !== null) {
               inputs += `<input type="hidden" name="answers[${questions[i].id}]" value="${ans}">`;
            }
         });
         document.getElementById('hiddenInputsContainer').innerHTML = inputs;
         document.getElementById('examForm').submit();
      }

      renderQuestion();
   </script>
</body>

</html>