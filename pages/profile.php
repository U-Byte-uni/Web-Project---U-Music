<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['username'])) {
  header('Location: ../index.php');
  exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
  header('Location: manage.php');
  exit;
}

$status_message = '';
$status_class = '';

$current_username = $_SESSION['username'];
$profile = [
  'username' => $current_username,
  'email' => '',
  'description' => ''
];

try {
  $stmt = $mysqli->prepare('SELECT username, email, description FROM users WHERE username = ? LIMIT 1');
  if ($stmt) {
    $stmt->bind_param('s', $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
      $row = $result->fetch_assoc();
      $profile['username'] = $row['username'] ?? $current_username;
      $profile['email'] = $row['email'] ?? '';
      $profile['description'] = $row['description'] ?? '';
    }
    $stmt->close();
  }
} catch (mysqli_sql_exception $e) {
  error_log('Profile load error: ' . $e->getMessage());
  $status_message = 'Unable to load profile details right now.';
  $status_class = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $new_username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $new_password = trim($_POST['new_password'] ?? '');
  $errors = [];

  if ($new_username === '') {
    $errors[] = 'Username is required.';
  }

  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
  }

  if ($new_password !== '' && (strlen($new_password) < 6 || strlen($new_password) > 255)) {
    $errors[] = 'New password must be between 6 and 255 characters.';
  }

  if (empty($errors)) {
    $profile['username'] = $new_username;
    $profile['email'] = $email;
    $profile['description'] = $description;

    try {
      if ($new_password === '') {
        $stmt = $mysqli->prepare(
          'UPDATE users SET username = ?, email = ?, description = ? WHERE username = ?'
        );
        if ($stmt) {
          $stmt->bind_param('ssss', $new_username, $email, $description, $current_username);
        }
      } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        if ($hashed === false) {
          $stmt = null;
          $errors[] = 'Unable to update password right now.';
        } else {
          $stmt = $mysqli->prepare(
            'UPDATE users SET username = ?, email = ?, description = ?, pwd = ? WHERE username = ?'
          );
          if ($stmt) {
            $stmt->bind_param('sssss', $new_username, $email, $description, $hashed, $current_username);
          }
        }
      }

      if (empty($errors)) {
        if ($stmt && $stmt->execute()) {
          $stmt->close();
          $_SESSION['username'] = $new_username;
          header('Location: gallery.php');
          exit;
        }

        if ($stmt) {
          $stmt->close();
        }
        $errors[] = 'Failed to update profile. Please try again.';
      }
    } catch (mysqli_sql_exception $e) {
      error_log('Profile update error: ' . $e->getMessage());
      $errors[] = 'Failed to update profile. Please try again.';
    }
  }

  if (!empty($errors)) {
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
      src: url("../assets/fonts/Lucy.ttf") format('truetype');
      font-weight: normal;
      font-style: normal;
    }

    @font-face {
      font-family: 'Transity';
      src: url("../assets/fonts/BeautifulDream.otf") format('opentype');
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: url(../assets/img/Bac.jpg) center/cover no-repeat fixed;
      color: #fff;
      min-height: 100vh;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px 60px;
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
      padding: 60px 0 20px;
      margin-top: 20px;
    }

    .profile-card {
      width: 100%;
      max-width: 620px;
      padding: 32px;
      background-color: rgba(0, 0, 0, 0.35);
      border-radius: 16px;
      box-shadow: 0 0 24px rgba(255, 114, 0, 0.35);
      backdrop-filter: blur(6px);
    }

    .profile-card h1 {
      text-align: center;
      margin-bottom: 24px;
      color: #ff7200;
      font-family: 'Transity', sans-serif;
      font-size: 36px;
    }

    .profile-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .profile-form label {
      font-weight: 500;
      margin-bottom: 6px;
      display: block;
    }

    .profile-form input,
    .profile-form textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 10px;
      background-color: rgba(0, 0, 0, 0.45);
      color: #fff;
      font-size: 16px;
      resize: none;
    }

    .profile-form input:focus,
    .profile-form textarea:focus {
      border-color: #ff7200;
      outline: none;
    }

    .profile-form textarea {
      min-height: 120px;
    }

    .profile-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 4px;
    }

    .profile-form button {
      background-color: #ff7200;
      color: #000;
      border: none;
      padding: 12px 26px;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      font-weight: 600;
    }

    .profile-form button:hover {
      background-color: #fff;
    }

    .form-status {
      margin-bottom: 16px;
      padding: 10px 12px;
      border-radius: 8px;
      font-size: 14px;
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
        position: relative;
        padding-right: 84px;
      }

      .navbar > .logout-btn {
        position: absolute;
        top: 0;
        right: 20px;
      }

      .menu ul {
        flex-wrap: wrap;
        justify-content: center;
      }

      .menu ul li {
        margin: 10px;
      }

      .profile-card {
        padding: 24px;
      }

      .profile-card h1 {
        font-size: 30px;
      }
    }

    @media (max-width: 480px) {
      .profile-wrapper {
        padding: 40px 0 10px;
      }

      .navbar {
        padding-right: 74px;
      }

      .profile-card {
        padding: 20px;
        box-shadow: 0 0 14px rgba(255, 114, 0, 0.25);
        backdrop-filter: blur(4px);
      }

      .profile-card h1 {
        font-size: 26px;
      }

      .profile-actions {
        justify-content: stretch;
      }

      .profile-form button {
        width: 100%;
        min-height: 44px;
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
          <li><a href="contact.php">Contact</a></li>
          <li><a href="about.php">About</a></li>
        </ul>
      </div>
      <a href="/Project/pages/logout.php" class="logout-btn" title="Logout">
        <img src="../assets/img/logout.png" alt="Logout">
      </a>
    </div>

    <div class="profile-wrapper">
      <div class="profile-card">
        <h1>Profile Settings</h1>

        <?php if ($status_message !== ''): ?>
          <div class="form-status <?php echo htmlspecialchars($status_class); ?>">
            <?php echo htmlspecialchars($status_message); ?>
          </div>
        <?php endif; ?>

        <form method="post" class="profile-form">
          <?php echo csrf_field(); ?>
          <div>
            <label for="username">Username</label>
            <input id="username" type="text" name="username" value="<?php echo htmlspecialchars($profile['username']); ?>" required>
          </div>
          <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>">
          </div>
          <div>
            <label for="new_password">New Password (optional)</label>
            <input id="new_password" type="password" name="new_password" placeholder="Leave blank to keep current password">
          </div>
          <div>
            <label for="description">About Me</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($profile['description']); ?></textarea>
          </div>
          <div class="profile-actions">
            <button type="submit">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
