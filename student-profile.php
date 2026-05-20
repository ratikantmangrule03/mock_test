<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
 header("Location: login.php");
 exit;
}

$student_name = htmlspecialchars($_SESSION['name']);
$student_initial = substr($student_name, 0, 1);

// Fetch user data to display username
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!doctype html>
<html lang="en">

<head>
 <meta charset="UTF-8" />
 <title>My Profile</title>
 <link rel="stylesheet" href="style.css" />
 <link
  rel="icon"
  type="image/x-icon"
  href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
</head>

<body>
 <div class="navbar">
  <h2><i class="fa-solid fa-user-graduate"></i> My Profile</h2>
  <div class="nav-profile" onclick="document.getElementById('student-dropdown-profile').classList.toggle('active')">
   <div class="avatar"><?= $student_initial ?></div>
   <div class="dropdown" id="student-dropdown-profile">
    <div class="dropdown-item">
     <strong style="color: #0f172a; font-size: 1rem; display: block"><?= $student_name ?></strong>
     <span style="font-size: 0.8rem">Student Account</span>
    </div>
    <button class="dropdown-btn" onclick="window.location.href='logout.php'">
     <i class="fa-solid fa-right-from-bracket"></i> Sign Out
    </button>
   </div>
  </div>
 </div>

 <div class="container slide-up">
  <div class="detail-header">
   <button class="back-btn" onclick="window.location.href='student-dashboard.php'" title="Back"><i class="fa-solid fa-arrow-left"></i></button>
  </div>

  <div class="card" style="max-width: 450px; margin: 0 auto; padding: 40px; text-align: center;">
   <div class="avatar" style="width: 80px; height: 80px; font-size: 2.5rem; margin: 0 auto 20px;"><?= $student_initial ?></div>
   <h2 style="font-size: 1.6rem; color: #0f172a; margin-bottom: 8px"><?= $student_name ?></h2>
   <p style="color: #64748b; font-weight: 500; margin-bottom: 32px"><i class="fa-solid fa-graduation-cap"></i> Student Account</p>

   <div style="text-align: left; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 24px;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
     <span style="color: #64748b; font-weight: 600"><i class="fa-solid fa-user"></i> Username</span>
     <span style="font-weight: 700; color: #0f172a"><?= htmlspecialchars($user['username']) ?></span>
    </div>
    <div style="display: flex; justify-content: space-between;">
     <span style="color: #64748b; font-weight: 600"><i class="fa-solid fa-lock"></i> Password</span>
     <span style="font-weight: 700; color: #0f172a">********</span>
    </div>
    <p style="font-size: 0.8rem; color: #94a3b8; text-align: center; margin-top: 20px; margin-bottom: 0;">
     *Passwords are securely hashed in the database and cannot be displayed in plain text.
    </p>
   </div>
  </div>
 </div>
</body>

</html>