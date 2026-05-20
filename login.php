<?php
require 'db.php';

if (isset($_SESSION['user_id'])) {
  header("Location: " . ($_SESSION['role'] === 'admin' ? "admin-dashboard.php" : "student-dashboard.php"));
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  // Fetch the user to check the hashed password securely
  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    header("Location: " . ($user['role'] === 'admin' ? "admin-dashboard.php" : "student-dashboard.php"));
    exit;
  } else {
    $error = "Invalid username or password!";
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login - MOCK TEST PORTAL</title>
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
          <div style="width: 64px; height: 64px; background: #3b82f6; color: white; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 16px; transform: rotate(-10deg);">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <h1 style="font-size: 1.8rem; font-weight: 700; color: #0f172a">Welcome Back</h1>
          <p style="color: #64748b; font-size: 0.95rem">Login to your Learning Portal</p>
        </div>

        <?php if ($error): ?>
          <div style="color: #dc2626; background: #fef2f2; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #fecaca;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
          <div class="input-group">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="username" placeholder="Username" required />
          </div>
          <div class="input-group">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required />
            <i class="fa-solid fa-eye toggle-pwd" id="togglePasswordBtn" onclick="togglePassword()"></i>
          </div>
          <button type="submit" class="btn-primary btn-block mt-4">
            Sign In <i class="fa-solid fa-arrow-right"></i>
          </button>

          <div class="text-center mt-4" style="font-size: 0.9rem;">
            <span style="color: #64748b;">Don't have an account?</span>
            <a href="register.php" style="color: #3b82f6; font-weight: 600;">Register Here</a>
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