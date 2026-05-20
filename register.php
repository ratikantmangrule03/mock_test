<?php
require 'db.php';

if (isset($_SESSION['user_id'])) {
 header("Location: " . ($_SESSION['role'] === 'admin' ? "admin-dashboard.php" : "student-dashboard.php"));
 exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $name = trim($_POST['name']);
 $username = trim($_POST['username']);
 $password = $_POST['password'];

 $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
 $stmt->execute([$username]);

 if ($stmt->fetch()) {
  $error = "Username already taken!";
 } else {
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  $role = 'student';

  $insert = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");

  if ($insert->execute([$name, $username, $hashed_password, $role])) {
   $success = "Registration successful! You can now sign in.";
  } else {
   $error = "Registration failed. Please try again.";
  }
 }
}
?>
<!doctype html>
<html lang="en">

<head>
 <meta charset="UTF-8">
 <title>Register - MOCK TEST PORTAL</title>
 <link
  rel="icon"
  type="image/x-icon"
  href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwCET6iQVYqWA7NZosvZYWzJNAGUuEhqDWvg&s" />
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
 <link rel="stylesheet" href="style.css" />
</head>

<body style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
 <div class="view" style="display: flex;">
  <div class="center-wrap">
   <div class="card slide-up" style="width: 100%; max-width: 440px; padding: 48px 40px;">
    <div class="text-center mb-4">
     <div style="width: 64px; height: 64px; background: #10b981; color: white; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 16px;">
      <i class="fa-solid fa-user-plus"></i>
     </div>
     <h1 style="font-size: 1.8rem; font-weight: 700; color: #0f172a">Create Account</h1>
     <p style="color: #64748b; font-size: 0.95rem">Register as a Student</p>
    </div>

    <?php if ($error): ?>
     <div style="color: #dc2626; background: #fef2f2; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #fecaca;">
      <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
     </div>
    <?php endif; ?>

    <?php if ($success): ?>
     <div style="color: #15803d; background: #f0fdf4; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #bbf7d0;">
      <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
     </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
     <div class="input-group">
      <i class="fa-solid fa-id-card"></i>
      <input type="text" name="name" placeholder="Full Name" required />
     </div>
     <div class="input-group">
      <i class="fa-solid fa-user"></i>
      <input type="text" name="username" placeholder="Choose Username" required />
     </div>
     <div class="input-group">
      <i class="fa-solid fa-lock"></i>
      <input type="password" name="password" id="password" placeholder="Create Password" required />
      <i class="fa-solid fa-eye toggle-pwd" id="togglePasswordBtn" onclick="togglePassword()"></i>
     </div>
     <button type="submit" class="btn-primary btn-block mt-4" style="background: #10b981;">
      Register <i class="fa-solid fa-arrow-right"></i>
     </button>

     <div class="text-center mt-4" style="font-size: 0.9rem;">
      <span style="color: #64748b;">Already have an account?</span>
      <a href="login.php" style="color: #3b82f6; font-weight: 600;">Sign In</a>
     </div>
    </form>
   </div>
  </div>
 </div>
 <script>
  function togglePassword() {
   const pwdInput = document.getElementById("password");
   const toggleBtn = document.getElementById("togglePasswordBtn");
   if (pwdInput.type === "password") {
    pwdInput.type = "text";
    toggleBtn.classList.replace("fa-eye", "fa-eye-slash");
   } else {
    pwdInput.type = "password";
    toggleBtn.classList.replace("fa-eye-slash", "fa-eye");
   }
  }
 </script>
</body>

</html>