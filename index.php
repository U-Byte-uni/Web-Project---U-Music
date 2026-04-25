<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/csrf.php';

$error = "";
$username_input = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  csrf_verify();

  $username_input = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username_input === '' || $password === '') {
    $error = "Please enter your username and password.";
  } elseif (strlen($username_input) > 50) {
    $error = "Username is too long.";
  } elseif (strlen($password) > 255) {
    $error = "Password is too long.";
  } else {
    try {
      $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
      $stmt->bind_param("s", $username_input);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        $stored_pwd = $user['pwd'];
        $hash_info = password_get_info($stored_pwd);
        $is_hashed = $hash_info['algo'] !== 0;
        $is_valid = false;

        if ($is_hashed) {
          $is_valid = password_verify($password, $stored_pwd);
        } else {
          $is_valid = hash_equals($stored_pwd, $password);

          if ($is_valid) {
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($new_hash !== false) {
              $update = $mysqli->prepare("UPDATE users SET pwd = ? WHERE user_id = ?");
              $update->bind_param("si", $new_hash, $user['user_id']);
              $update->execute();
              $update->close();
              $stored_pwd = $new_hash;
            }
          }
        }

        if ($is_valid) {
          session_regenerate_id(true);
          $_SESSION['user_id'] = $user['user_id'];
          $_SESSION['username'] = $user['username'];
          $_SESSION['role'] = $user['role'];

          if ($_SESSION['role'] === 'admin') {
            echo "<script>window.location.href='pages/manage.php';</script>";
          } else {
            echo "<script>window.location.href='pages/wellcome.php';</script>";
          }
          exit;
        }

        $error = "Invalid username or password. Please try again.";
      } else {
        $error = "Invalid username or password. Please try again.";
      }

      $stmt->close();
    } catch (mysqli_sql_exception $e) {
      error_log('Login error: ' . $e->getMessage());
      $error = "Login is temporarily unavailable. Please try again.";
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap");

    :root {
      --white-color: hsl(0, 0%, 100%);
      --black-color: hsl(0, 0%, 0%);
      --body-font: "Poppins", sans-serif;
      --h1-font-size: 1.75rem;
      --normal-font-size: 1rem;
      --small-font-size: 0.813rem;
      --font-medium: 500;
    }

    * {
      box-sizing: border-box;
      padding: 0;
      margin: 0;
    }

    body,
    input,
    button {
      font-size: var(--normal-font-size);
      font-family: var(--body-font);
    }

    body {
      color: white;
    }

    input,
    button {
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

    .login__content,
    .login__box {
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

    .login__box-input {
      position: relative;
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

    .login__register {
      font-size: var(--small-font-size);
      text-align: center;
    }

    .login__button {
      width: 100%;
      padding: 1rem;
      border-radius: 0.5rem;
      background-color: white;
      font-weight: var(--font-medium);
      cursor: pointer;
      margin-bottom: 1rem;
    }

    .login__button:hover {

      background-color: #ff7200;
      transition: background-color 0.3s ease;

    }

    .login__register a {
      color: white;
      font-weight: bold;
    }

    .login__register a:hover {
      text-decoration: underline;
    }

    .error-message {
      color: #ff8080;
      text-align: center;
      font-size: var(--small-font-size);
      margin-bottom: 1.2rem;
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
    <img src="img/Background.jpg" alt="login image" class="login__img" />

    <form method="post" class="login__form">
      <?php echo csrf_field(); ?>
      <h1 class="login__title">Login</h1>

      <?php if (!empty($error)) {
        echo "<div class='error-message'>" . htmlspecialchars($error) . "</div>";
      } ?>

      <div class="login__content">
        <div class="login__box">
          <div class="login__box-input">
            <input type="text" name="username" required class="login__input" placeholder="Username"
              value="<?php echo htmlspecialchars($username_input); ?>" />
          </div>
        </div>

        <div class="login__box">
          <div class="login__box-input">
            <input type="password" name="password" required class="login__input" id="login-pas"
              placeholder="Password" />
          </div>
        </div>

        <div class="login__show-password">
          <input type="checkbox" id="show-password" />
          <label for="show-password">Show Password</label>
        </div>
      </div>

      <button type="submit" class="login__button">Login</button>

      <p class="login__register">
        Don't have an account? <a href="pages/register.php">Register</a>
      </p>
    </form>
  </div>

  <script>
    document.getElementById("show-password").addEventListener("change", function () {
      const passwordInput = document.getElementById("login-pas");
      passwordInput.type = this.checked ? "text" : "password";
    });
  </script>
</body>

</html>