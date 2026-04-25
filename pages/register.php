<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
  && isset($_POST['username'], $_POST['email'], $_POST['password'])) {

  csrf_verify();

  $username = trim($_POST['username']);
  $email    = trim($_POST['email']);
  $password = trim($_POST['password']);
  $errors = [];

  if ($username === '') {
    $errors[] = 'Username is required.';
  } elseif (strlen($username) < 3 || strlen($username) > 50) {
    $errors[] = 'Username must be between 3 and 50 characters.';
  }

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
  } elseif (strlen($email) > 255) {
    $errors[] = 'Email is too long.';
  }

  if ($password === '') {
    $errors[] = 'Password is required.';
  } elseif (strlen($password) < 6 || strlen($password) > 255) {
    $errors[] = 'Password must be between 6 and 255 characters.';
  }

  if (!empty($errors)) {
    $form_error = implode(' ', $errors);
  } else {
    try {
      $stmt = $mysqli->prepare(
        'SELECT 1 FROM users WHERE username = ? LIMIT 1'
      );
      $stmt->bind_param('s', $username);
      $stmt->execute();
      $stmt->store_result();

      if ($stmt->num_rows) {
        $form_error = 'Username already in use. Please choose another one.';
      } else {
        $role = ($username === 'admin') ? 'admin' : 'user';
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $mysqli->prepare(
          'INSERT INTO users (username, pwd, email, role)
           VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $username, $password_hash, $email, $role);

        if ($stmt->execute()) {
          echo "<script>
            alert('Registration successful!');
            window.location.href = 'wellcome.php';
          </script>";
        } else {
          $form_error = 'Registration failed. Please try again.';
        }
      }

      $stmt->close();
    } catch (mysqli_sql_exception $e) {
      error_log('Registration error: ' . $e->getMessage());
      $form_error = 'Registration is temporarily unavailable. Please try again.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registration</title>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap");

    :root {
      --white-color: hsl(0,0%,100%);
      --black-color: hsl(0, 0%, 0%);
      --body-font: "Poppins", sans-serif;
      --h1-font-size: 1.75rem;
      --normal-font-size: 1rem;
      --small-font-size: .813rem;
      --font-medium: 500;
    }

    * {
      box-sizing: border-box;
      padding: 0;
      margin: 0;
    }

    body, input, button {
      font-size: var(--normal-font-size);
      font-family: var(--body-font);
    }

    body {
      background-color: black;
      color: white;
    }

    input, button {
      border: none;
      outline: none;
    }

    input::placeholder {
      color: white;
    }

    img {
      max-width: 100%;
      height: auto;
    }

    .login {
      position: relative;
      height: 100vh;
      display: grid;
      align-items: center;
    }

    .login__img {
      position: absolute;
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
      z-index: -1;
    }

    .login__form {
      position: relative;
      background-color: hsla(0, 0%, 10%, 0.1);
      border: 2px solid var(--white-color);
      margin-inline: 1.5rem;
      padding: 2.5rem 1.5rem;
      border-radius: 1rem;
      backdrop-filter: blur(8px);
    }

    .login__title {
      text-align: center;
      font-size: var(--h1-font-size);
      font-weight: var(--font-medium);
      margin-bottom: 2rem;
    }

    .login__content, .login__box {
      display: grid;
    }

    .login__content {
      row-gap: 1.75rem;
      margin-bottom: 1.5rem;
    }

    .login__box {
      grid-template-columns: max-content 1fr;
      align-items: center;
      column-gap: 0.75rem;
      border-bottom: 2px solid white;
    }

    .login__input {
      width: 100%;
      padding-block: 0.8rem;
      background: none;
      color: white;
      position: relative;
      z-index: 1;
    }

    .login__show-password {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      font-size: var(--small-font-size);
      color: white;
      margin-top: 0.5rem;
    }

    .login__show-password input {
      width: 14px;
      height: 14px;
      margin-left: 0.5rem;
      cursor: pointer;
      accent-color: white;
    }

    .login__button {
      width: 100%;
      padding: 1rem;
      border-radius: 0.5rem;
      background-color: white;
      font-weight: var(--font-medium);
      cursor: pointer;
      margin-bottom: 2rem;
    }

    .login__button:hover {
      background-color: #ff7200;
      transition: background-color 0.3s ease;
    }

    .login__register {
      text-align: center;
      font-size: var(--small-font-size);
    }

    .login__register a {
      color: white;
      font-weight: bold;
    }

    .login__register a:hover {
      text-decoration: underline;
    }

    .error-message {
      color: red;
      font-size: 0.875rem;
      margin-bottom: 0.5rem;
      display: none;
    }

    @media screen and (min-width: 576px) {
      .login {
        justify-content: center;
      }

      .login__form {
        width: 432px;
        padding: 4rem 3rem 3.5rem;
        border-radius: 1.5rem;
      }

      .login__title {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
<div class="login">
  <img src="../img/Bac.jpg" alt="login image" class="login__img">
  <form action="register.php" method="post" class="login__form">
    <?php echo csrf_field(); ?>
    <h1 class="login__title">Register</h1>
    <div class="login__content">
      <p id="username-error" class="error-message">Username already in use. Please choose another one.</p>
      <?php if ($form_error !== ''): ?>
        <div class="error-message" style="display: block;">
          <?php echo htmlspecialchars($form_error); ?>
        </div>
      <?php endif; ?>

      <div class="login__box">
        <div class="login__box-input">
          <input type="text" name="username" required class="login__input" placeholder="Username" id="username">
        </div>
      </div>

      <div class="login__box">
        <div class="login__box-input">
          <input type="email" name="email" required class="login__input" placeholder="Email">
        </div>
      </div>

      <div class="login__box">
        <div class="login__box-input">
          <input type="password" name="password" required class="login__input" id="login-pas" placeholder="Password">
        </div>
      </div>

      <div class="login__show-password">
        <input type="checkbox" id="show-password">
        <label for="show-password">Show Password</label>
      </div>

      <button type="submit" name="register" class="login__button">Register</button>

      <p class="login__register">
        Already a member? <a href="/">Login</a>
      </p>
    </div>
  </form>
</div>

<script>
document.getElementById('show-password').addEventListener('change', function () {
  const pw = document.getElementById('login-pas');
  pw.type = this.checked ? 'text' : 'password';
});
</script>
</body>
</html>
