<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

$status_message = '';
$status_class = '';

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
$stored_hash = '';

if ($user_id !== null) {
  try {
    $stmt = $mysqli->prepare('SELECT username, role, pwd FROM users WHERE user_id = ? LIMIT 1');
    if ($stmt) {
      $stmt->bind_param('i', $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $role = $row['role'];
        $stored_hash = $row['pwd'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
      }
      $stmt->close();
    }
  } catch (mysqli_sql_exception $e) {
    error_log('Profile load error: ' . $e->getMessage());
    $status_message = 'Unable to load profile details right now.';
    $status_class = 'error';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $errors = [];

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $errors[] = 'All password fields are required.';
    }

    if ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if ($new_password !== '' && (strlen($new_password) < 6 || strlen($new_password) > 255)) {
      $errors[] = 'New password must be between 6 and 255 characters.';
    }

    if (empty($errors)) {
        if ($stored_hash === '') {
          try {
            $stmt = $mysqli->prepare('SELECT pwd FROM users WHERE user_id = ? LIMIT 1');
            if ($stmt) {
              $stmt->bind_param('i', $user_id);
              $stmt->execute();
              $result = $stmt->get_result();
              if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $stored_hash = $row['pwd'];
              }
              $stmt->close();
            }
          } catch (mysqli_sql_exception $e) {
            error_log('Profile load error: ' . $e->getMessage());
            $status_message = 'Unable to load profile details right now.';
            $status_class = 'error';
          }
        }

        if ($stored_hash !== '' && password_verify($current_password, $stored_hash)) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            if ($new_hash !== false) {
            try {
              $stmt = $mysqli->prepare('UPDATE users SET pwd = ? WHERE user_id = ?');
              if ($stmt) {
                $stmt->bind_param('si', $new_hash, $user_id);
                if ($stmt->execute()) {
                  $status_message = 'Password updated successfully.';
                  $status_class = 'success';
                } else {
                  $status_message = 'Failed to update password. Please try again.';
                  $status_class = 'error';
                }
                $stmt->close();
              } else {
                $status_message = 'Failed to update password. Please try again.';
                $status_class = 'error';
              }
            } catch (mysqli_sql_exception $e) {
              error_log('Profile update error: ' . $e->getMessage());
              $status_message = 'Failed to update password. Please try again.';
              $status_class = 'error';
            }
            } else {
                $status_message = 'Failed to update password. Please try again.';
                $status_class = 'error';
            }
        } else {
            $status_message = 'Current password is incorrect.';
            $status_class = 'error';
        }
    } else {
        $status_message = implode(' ', $errors);
        $status_class = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile - U-Music</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
  <style>
    @font-face {
      font-family: 'Lucy';
      src: url("../fonts/Lucy.ttf") format('truetype');
      font-weight: normal;
      font-style: normal;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: url(../img/Background.jpg) center/cover no-repeat fixed;
      color: #fff;
      min-height: 100vh;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .navbar {
      max-width: 1200px;
      height: 75px;
      margin: auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 20px;
      position: relative;
      top: 20px;
    }

    .logo {
      display: flex;
      align-items: center;
      font-size: 48px;
      letter-spacing: 2px;
      font-weight: 700;
      cursor: default;
    }

    .logo-u {
      font-family: 'Lucy';
      font-size: 70px;
      color: rgb(233, 36, 36);
      margin-right: 6px;
      text-shadow: 2px 2px 10px rgba(255, 207, 0, 0.8);
    }

    .logo-music {
      font-family: 'Poppins', sans-serif;
      font-weight: 800;
      background: linear-gradient(90deg, #ff3c00, #ffa600, #ff3c00);
      background-size: 200% auto;
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: shine 3s linear infinite;
    }

    .menu ul {
      display: flex;
      list-style: none;
    }

    .menu ul li {
      margin-left: 40px;
    }

    .menu ul li a {
      text-decoration: none;
      color: #fff;
      font-weight: bold;
      transition: 0.3s;
    }

    .menu ul li a:hover {
      color: #ff7200;
    }

    .logout-btn {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 50px;
      height: 50px;
      background-color: #ff7200;
      border-radius: 50%;
      text-decoration: none;
    }

    .logout-btn img {
      width: 45px;
      height: 45px;
    }

    .profile-wrapper {
      display: flex;
      justify-content: center;
      padding: 40px 0;
      margin-top: 20px;
    }

    .profile-card {
      width: 100%;
      max-width: 520px;
      padding: 30px;
      background-color: rgba(0, 0, 0, 0.26);
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(255, 114, 0, 0.3);
      backdrop-filter: blur(5px);
    }

    .profile-card h1 {
      text-align: center;
      margin-bottom: 20px;
      color: #ff7200;
    }

    .profile-meta {
      margin-bottom: 20px;
      font-size: 16px;
      line-height: 1.6;
    }

    .profile-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .profile-form input {
      padding: 12px;
      border: 2px solid #444;
      border-radius: 8px;
      background-color: #222;
      color: #fff;
      font-size: 16px;
    }

    .profile-form input:focus {
      border-color: #ff7200;
      outline: none;
    }

    .profile-form button {
      background-color: #fff;
      color: #000;
      border: none;
      padding: 14px;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .profile-form button:hover {
      background-color: #ff7200;
    }

    .form-status {
      margin-bottom: 15px;
      padding: 10px 12px;
      border-radius: 8px;
      font-size: 14px;
    }

    .form-status.success {
      background-color: rgba(0, 128, 0, 0.2);
      border: 1px solid rgba(0, 128, 0, 0.6);
    }

    .form-status.error {
      background-color: rgba(200, 0, 0, 0.2);
      border: 1px solid rgba(200, 0, 0, 0.6);
    }

    @keyframes shine {
      0% { background-position: 0% center; }
      100% { background-position: 200% center; }
    }

    @media (max-width: 768px) {
      .navbar {
        flex-direction: column;
        gap: 10px;
        height: auto;
      }

      .menu ul {
        flex-wrap: wrap;
        justify-content: center;
      }

      .menu ul li {
        margin: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="navbar">
      <h2 class="logo"><span class="logo-u">U</span><span class="logo-music">-Music</span></h2>
      <div class="menu">
        <ul>
          <li><a href="home.php">Home</a></li>
          <li><a href="gallery.php">Gallery</a></li>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="manage.php">Manage</a></li>
          <?php endif; ?>
          <li><a href="profile.php">Profile</a></li>
          <li><a href="contact.php">Contact</a></li>
          <li><a href="about.php">About</a></li>
        </ul>
      </div>
      <a href="/Project/pages/logout.php" class="logout-btn" title="Logout">
        <img src="../img/logout.png" alt="Logout">
      </a>
    </div>

    <div class="profile-wrapper">
      <div class="profile-card">
        <h1>Your Profile</h1>
        <div class="profile-meta">
          <div>Username: <?php echo htmlspecialchars($username); ?></div>
          <div>Role: <?php echo htmlspecialchars($role); ?></div>
        </div>

        <?php if ($status_message !== ''): ?>
          <div class="form-status <?php echo htmlspecialchars($status_class); ?>">
            <?php echo htmlspecialchars($status_message); ?>
          </div>
        <?php endif; ?>

        <form method="post" class="profile-form">
          <?php echo csrf_field(); ?>
          <input type="password" name="current_password" placeholder="Current Password" required>
          <input type="password" name="new_password" placeholder="New Password" required>
          <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
          <button type="submit">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
