<?php
/**
 * Gallery Controller
 *
 * Handles gallery rendering plus AJAX actions for history and favourites.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

/** @var string $searchTerm Filter term used in song search. */
$searchTerm = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
  $searchTerm = trim($_GET['search']);
}

// Protect all state-changing requests before processing POST handlers.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
}

// Legacy history endpoint branch retained for current frontend compatibility.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_history'])) {
  if (isset($_SESSION['user_id'], $_POST['song_id'])) {
    $user_id = $_SESSION['user_id'];
    $song_id = intval($_POST['song_id']);

    $stmt = $mysqli->prepare("INSERT INTO history (user_id, song_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $song_id);

    if ($stmt->execute()) {
      echo "Recorded";
    } else {
      http_response_code(500);
      echo "Database error";
    }

    $stmt->close();
  } else {
    http_response_code(400);
    echo "Invalid user or song ID";
  }
  exit;
}

// Main POST API branch used by AJAX calls from the gallery UI.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: text/plain');

  if (isset($_POST['record_history']) && isset($_SESSION['user_id'], $_POST['song_id'])) {
    $user_id = $_SESSION['user_id'];
    $song_id = intval($_POST['song_id']);

    $stmt = $mysqli->prepare("INSERT INTO history (user_id, song_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $song_id);

    echo $stmt->execute() ? "Recorded" : "Database error";
    $stmt->close();
    exit;
  }

  // Favourite toggle endpoint used by heart icon interactions.
  if (isset($_POST['song_id'], $_POST['action'], $_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $song_id = intval($_POST['song_id']);
    $action = $_POST['action'];

    if ($action === 'add') {
      $stmt = $mysqli->prepare("INSERT IGNORE INTO favourite (user_id, song_id) VALUES (?, ?)");
      $stmt->bind_param("ii", $user_id, $song_id);
      $stmt->execute();
      echo "added";
      $stmt->close();
      exit;
    } elseif ($action === 'remove') {
      $stmt = $mysqli->prepare("DELETE FROM favourite WHERE user_id = ? AND song_id = ?");
      $stmt->bind_param("ii", $user_id, $song_id);
      $stmt->execute();
      echo "removed";
      $stmt->close();
      exit;
    }
  }

  exit("Invalid request");
}

// Return favourite song IDs so the UI can highlight active hearts on load.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['favourite_list']) && isset($_SESSION['user_id'])) {
  header('Content-Type: application/json');

  $user_id = $_SESSION['user_id'];
  $result = $mysqli->query("SELECT song_id FROM favourite WHERE user_id = $user_id");

  $favs = [];
  while ($row = $result->fetch_assoc()) {
    $favs[] = (string) $row['song_id'];
  }
  echo json_encode($favs);
  exit;
}

?>

<?php /* ===================== HTML Rendering ===================== */ ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Gallery - U-Music</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap");

    @font-face {
      font-family: 'Lucy';
      src: url("../assets/fonts/Lucy.ttf") format('truetype');
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
      font-family: "Poppins", sans-serif;
      background: url('../assets/img/Bac.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      color: #fff;
    }

    .main {
      display: flex;
      min-height: 100vh;
      padding-left: 50px;
      padding-top: 0px;
      transition: padding-left 0.3s ease;
    }

    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      width: 80px;
      height: 100vh;
      background: url('../assets/img/Bac.jpg') no-repeat center center fixed;
      background-size: cover;
      backdrop-filter: blur(6px);
      transition: width 0.3s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .sidebar.expanded {
      width: 220px;
    }

    .sidebar-toggle {
      position: absolute;
      top: 10px;
      right: 10px;
      background: none;
      border: none;
      color: #fff;
      font-size: 20px;
      cursor: pointer;
      z-index: 2;
    }

    .sidebar-buttons {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      gap: 30px;
    }

    .sidebar-buttons button,
    .sidebar-buttons a {
      background: none;
      border: none;
      color: white;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      transition: color 0.3s ease;
      position: relative;
      overflow: hidden;
      padding: 8px 12px;
      border-radius: 8px;
      z-index: 0;
      text-decoration: none;
    }

    .sidebar-buttons button::before,
    .sidebar-buttons a::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(255, 114, 0, 0.2);
      transform: translateX(-110%);
      transition: transform 0.3s ease-in-out;
      z-index: -1;
    }

    .sidebar-buttons button:hover::before,
    .sidebar-buttons a:hover::before {
      transform: translateX(0);
    }

    .sidebar-buttons button i,
    .sidebar-buttons a i {
      color: #ff7200;
      font-size: 24px;
      transition: transform 0.3s, color 0.3s, text-shadow 0.3s;
    }

    .sidebar-buttons button:hover i,
    .sidebar-buttons a:hover i {
      transform: scale(1.2);
      color: rgb(243, 185, 138);
      text-shadow: 0 0 8px #ff7200;
    }

    .sidebar.expanded .sidebar-buttons span {
      display: inline-block;
    }

    .sidebar.collapsed .sidebar-buttons span {
      display: none;
    }

    .content {
      flex: 1;
      padding: 20px;
      z-index: 1;
      transition: margin-left 0.3s ease;
      margin-left: 50px;
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
      background-color: transparent;
    }

    .mobile-menu-btn {
      display: none;
      width: 44px;
      height: 44px;
      border: none;
      border-radius: 10px;
      background: rgba(0, 0, 0, 0.35);
      color: #fff;
      cursor: pointer;
      align-items: center;
      justify-content: center;
    }

    .mobile-menu-btn i {
      font-size: 20px;
      color: #ff7200;
    }

    .logo {
      display: flex;
      align-items: center;
      font-size: 48px;
      font-weight: 700;
      letter-spacing: 2px;
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
      color: transparent;
      -webkit-text-fill-color: transparent;
      animation: shine 3s linear infinite;
      text-shadow: 2px 2px 10px rgba(255, 114, 0, 0.5);
    }

    @keyframes shine {
      0% {
        background-position: 0% center;
      }

      100% {
        background-position: 200% center;
      }
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
      transition: 0.3s ease-in-out;
      position: relative;
      overflow: hidden;
      display: inline-block;
      padding: 8px 12px;
      border-radius: 8px;
      z-index: 0;
    }

    .menu ul li a::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(255, 114, 0, 0.2);
      transform: translateX(-110%);
      transition: transform 0.3s ease-in-out;
      z-index: -1;
    }

    .menu ul li a:hover {
      color: #ff7200;
    }

    .menu ul li a:hover::before {
      transform: translateX(0);
    }

    .logout {
      display: flex;
      align-items: center;
      margin-left: 40px;
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
      overflow: hidden;
      transition: background 0.3s ease;
    }

    .logout-btn:hover {
      background-color: #fff;
    }

    .logout-btn img {
      width: 45px;
      height: 45px;
      pointer-events: none;
    }

    .search-bar-container {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      backdrop-filter: blur(4px);
      background-color: rgba(255, 255, 255, 0.1);
      border-radius: 30px;
      padding: 6px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
      max-width: 280px;
    }

    .search-bar {
      padding: 10px 14px;
      border: none;
      border-radius: 20px 0 0 20px;
      outline: none;
      font-size: 16px;
      width: 180px;
      background-color: transparent;
      color: white;
      height: 40px;
    }

    .search-bar::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }

    .search-button {
      padding: 10px 14px;
      border: none;
      background: linear-gradient(135deg, #ff7200, #ff9d00);
      color: white;
      border-radius: 0 20px 20px 0;
      cursor: pointer;
      transition: background 0.3s;
      font-weight: bold;
      height: 40px;
    }

    .search-button:hover {
      background: linear-gradient(135deg, #e66200, #ff8600);
    }

    .gallery-container {
      max-width: 1190px;
      margin: 100px auto 0;
      padding: 20px;
      width: 100%;
    }

    .gallery-title {
      font-family: 'Transity', sans-serif;
      font-size: 42px;
      margin-bottom: 30px;
      color: #ff7200;
      text-align: center;
    }

    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, 260px);
      gap: 15px;
      padding: 0 20px;
      justify-content: center;
      justify-items: start;
    }

    .gallery-grid img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 8px;
      display: block;
    }

    .sidebar-tab {
      display: none;
      padding: 20px;
      margin-top: 100px;
      color: white;
      text-align: center;
    }

    .section-title {
      font-family: 'Transity', sans-serif;
      font-size: 42px;
      margin-bottom: 40px;
      color: #ff7200;
      text-align: center;
    }


    .song-item {
      background-color: rgba(255, 255, 255, 0.08);
      padding: 10px;
      border-radius: 12px;
      text-align: left;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
      transition: transform 0.3s, box-shadow 0.3s;
      width: 260px;
      height: 280px;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 8px;
      backdrop-filter: blur(6px);
      overflow: hidden;
    }

    .song-item:hover {
      transform: translateY(-8px);
    }

    .song-thumb {
      position: relative;
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      aspect-ratio: 1 / 1;
      border-radius: 12px 12px 0 0;
      overflow: hidden;
    }

    .song-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 12px 12px 0 0;
      transition: filter 0.3s ease;
    }

    .song-item:hover .song-thumb img {
      filter: brightness(1.1);
    }

    .play-overlay {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }

    .play-overlay i {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: rgba(255, 114, 0, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ff7200;
      font-size: 20px;
    }

    .song-item:hover .play-overlay,
    .song-item:focus-within .play-overlay {
      opacity: 1;
    }

    .song-details {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      padding: 0 2px 2px;
      gap: 6px;
      min-width: 0;
    }

    .song-item audio {
      width: 100%;
      height: 28px;
      margin: 0;
    }

    .waveform {
      display: flex;
      justify-content: flex-start;
      align-items: flex-end;
      gap: 4px;
      height: 16px;
      margin: 0;
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .waveform span {
      width: 4px;
      height: 5px;
      background: #ff7200;
      border-radius: 2px;
      animation: quiet-pulse 0.9s ease-in-out infinite;
      animation-play-state: paused;
    }

    .waveform span:nth-child(2) {
      animation-delay: 0.15s;
    }

    .waveform span:nth-child(3) {
      animation-delay: 0.3s;
    }

    .waveform span:nth-child(4) {
      animation-delay: 0.45s;
    }

    .song-item:hover .waveform,
    .song-item:focus-within .waveform,
    .song-item.is-active .waveform {
      opacity: 1;
    }

    .song-item:hover .waveform span,
    .song-item:focus-within .waveform span,
    .song-item.is-active .waveform span {
      animation-play-state: running;
    }

    @keyframes quiet-pulse {
      0% {
        height: 5px;
      }

      100% {
        height: 15px;
      }
    }


    .song-title {
      font-size: 18px;
      color: #ffae42;
      font-weight: bold;
      margin-top: 0;
      text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.6);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    #history table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      color: white;
    }

    #history th,
    #history td {
      padding: 12px 20px;
      text-align: left;
    }

    #history th {
      background-color: #ff7200;
      color: white;
      font-size: 18px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    #history tr:nth-child(even) {
      background-color: rgba(255, 255, 255, 0.1);
    }

    #history tr:nth-child(odd) {
      background-color: rgba(255, 255, 255, 0.2);
    }

    #history tr:hover {
      background-color: rgba(255, 255, 255, 0.3);
      cursor: pointer;
    }

    #history td {
      font-size: 16px;
      color: rgb(255, 255, 255);
    }

    #history td a {
      color: rgb(255, 255, 255);
      text-decoration: none;
    }

    #history td a:hover {
      text-decoration: underline;
    }

    .song-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
      gap: 8px;
    }

    .song-info {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      text-align: left;
      min-width: 0;
      flex: 1;
    }

    .song-title {
      font-size: 18px;
      color: #ffae42;
      font-weight: bold;
      margin: 0;
      text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.6);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .song-artist {
      font-size: 14px;
      color: #eee;
      margin-top: 4px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .fav-icon {
      cursor: pointer;
      color: white;
      font-size: 22px;
      transition: color 0.3s ease;
    }

    .fav-icon.fav-active {
      color: hotpink;
    }

    .display-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: rgba(255, 255, 255, 0.1);
    }

    .display-table th,
    .display-table td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: left;
      color: white;
    }

    .display-table th {
      background-color: #ff7200;
      color: black;
    }

    .display-table tr:nth-child(even) {
      background-color: rgba(255, 255, 255, 0.2);
    }

    .display-table tr:hover {
      background-color: rgba(255, 114, 0, 0.5);
    }




    @media (max-width: 768px) {
      .navbar {
        flex-direction: column;
        height: auto;
        gap: 10px;
        position: relative;
        padding-right: 84px;
      }

      .logout {
        position: absolute;
        top: 0;
        right: 20px;
        margin-left: 0;
      }

      .menu ul {
        flex-direction: column;
        align-items: center;
      }

      .gallery-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        justify-items: stretch;
        padding: 0 10px;
      }

      .song-item {
        width: 100%;
      }

      .sidebar {
        width: 60px;
      }

      .sidebar.expanded {
        width: 180px;
      }

      .content {
        margin-left: 0;
      }
    }

    @media (max-width: 480px) {
      .mobile-menu-btn {
        display: inline-flex;
      }

      .navbar {
        align-items: center;
        text-align: center;
        padding-right: 74px;
      }

      .menu ul {
        flex-direction: column;
        gap: 8px;
      }

      .sidebar {
        width: 220px;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }

      .sidebar.collapsed {
        transform: translateX(-100%);
      }

      .sidebar.expanded {
        transform: translateX(0);
      }

      .main {
        padding-left: 0;
      }

      .content {
        margin-left: 0;
      }

      .gallery-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .song-item {
        width: 100%;
      }

      .search-bar-container {
        width: 100%;
        max-width: none;
      }

      .search-bar,
      .search-button {
        height: 44px;
      }

      .gallery-title {
        font-size: 32px;
      }
    }
  </style>
</head>

<body>
  <div class="main">

    <!-- Sidebar: profile shortcut + quick actions for favourites/history -->
    <div class="sidebar collapsed" id="sidebar">
      <button class="sidebar-toggle" onclick="toggleSidebar()" title="Toggle Sidebar">
        <i class="fas fa-angle-double-left" id="toggle-icon"></i>
      </button>
      <div class="sidebar-buttons">
        <a href="profile.php" class="sidebar-link">
          <i class="fas fa-user-circle"></i>
          <span><?php echo $_SESSION['username'] ?? 'Username'; ?></span>
        </a>
        <button type="button" onclick="showSidebarSection('favourites')">
          <i class="fas fa-star"></i>
          <span>Favourites</span>
        </button>
        <button type="button" onclick="showSidebarSection('history')">
          <i class="fas fa-clock-rotate-left"></i>
          <span>History</span>
        </button>
      </div>
    </div>


    <div class="content">
      <!-- Top navigation area -->
      <div class="navbar">
        <button class="mobile-menu-btn" type="button" onclick="toggleSidebar()" aria-label="Toggle sidebar">
          <i class="fas fa-bars"></i>
        </button>
        <a href="gallery.php" class="logo" style="text-decoration: none;">
          <span class="logo-u">U</span><span class="logo-music">-Music</span>
        </a>

        <div class="menu">
          <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
              <li><a href="manage.php">Manage</a></li>
            <?php endif; ?>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="about.php">About</a></li>
          </ul>
        </div>
        <div class="logout">
          <a href="/Project/pages/logout.php" class="logout-btn" title="Logout">
            <img src="../assets/img/logout.png" alt="Logout">
          </a>
        </div>
      </div>

      <!-- Main gallery content -->
      <div class="gallery-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
          <div style="flex: 1;"></div>
          <h2 class="gallery-title" style="margin-bottom: 0; flex: 1;">Gallery</h2>
          <div style="flex: 1; display: flex; justify-content: flex-end;">
            <!-- Song search form -->
            <form method="GET" class="search-bar-container" style="margin: 0;">
              <input type="text" class="search-bar" name="search" placeholder="Search..."
                value="<?php echo htmlspecialchars($_GET['search'] ?? '') ?>">
              <button class="search-button"><i class="fas fa-search"></i></button>
            </form>
          </div>
        </div>
        <div class="gallery-grid">
          <?php
          // Select filtered songs when a search term is present.
          if (!empty($searchTerm)) {
            $stmt = $mysqli->prepare("
                SELECT song_id, s_name, path, artist, added_at 
                FROM songs 
                WHERE s_name LIKE ? OR artist LIKE ? OR added_at LIKE ?
            ");
            $like = "%" . $searchTerm . "%";
            $stmt->bind_param("sss", $like, $like, $like);
            $stmt->execute();
            $result = $stmt->get_result();
          } else {
            $result = $mysqli->query("SELECT song_id, s_name, path, artist, added_at FROM songs");
          }


          if ($result && $result->num_rows > 0) {
            // Render each song card with media controls and favourite actions.
            while ($row = $result->fetch_assoc()) {
              $song_id = (int) $row['song_id'];
              $song_name = htmlspecialchars($row['s_name']);
              $artist = htmlspecialchars($row['artist']);
              $audio_path = htmlspecialchars($row['path']);
              $audio_src = $audio_path;
              // Normalize local file paths so <audio> source URLs remain valid.
              if (!preg_match('/^(https?:)?\\/\\//i', $audio_path)) {
                if (strpos($audio_path, 'assets/music/') === 0) {
                  $audio_src = "../" . $audio_path;
                } elseif (strpos($audio_path, 'music/') === 0) {
                  $audio_src = "../assets/" . $audio_path;
                } else {
                  $audio_src = "../assets/music/" . ltrim($audio_path, '/');
                }
              }

              // Prefer song-specific thumbnail; fallback to default image.
              $thumb_fs = __DIR__ . "/../assets/img/song_thumbs/{$song_name}.jpg";
              $image_path = file_exists($thumb_fs)
                ? "../assets/img/song_thumbs/{$song_name}.jpg"
                : "../assets/img/image.png";

              echo <<<HTML
                    <div class="song-item">
                      <div class="song-thumb">
                        <img src="{$image_path}" alt="Song Cover">
                        <div class="play-overlay" aria-hidden="true">
                          <i class="fas fa-play"></i>
                        </div>
                      </div>
                      <div class="song-details">
                        <audio controls data-song-id="{$song_id}">
                          <source src="{$audio_src}" type="audio/mpeg">
                          Your browser does not support the audio element.
                        </audio>
                        <div class="waveform" aria-hidden="true">
                          <span></span>
                          <span></span>
                          <span></span>
                          <span></span>
                        </div>
                        <div class="song-meta">
                          <div class="song-info">
                            <p class="song-title">{$song_name}</p>
                            <p class="song-artist">{$artist}</p>
                          </div>
                          <div class="fav-icon" data-song-id="{$song_id}">
                            <i class="fas fa-heart"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                    HTML;

            }
          } else {
            echo "<p>No songs available.</p>";
          }
          ?>
        </div>
      </div>


      <!-- Favourites view shown via sidebar -->
      <div id="favourites" class="sidebar-tab">
        <h2 class="section-title">Your Favourites</h2>

        <?php
        if (isset($_SESSION['user_id'])) {
          $user_id = $_SESSION['user_id'];
          $stmt = $mysqli->prepare("
          SELECT f.fav_id, s.s_name, f.added_at
          FROM favourite f
          JOIN songs s ON f.song_id = s.song_id
          WHERE f.user_id = ?
          ORDER BY f.added_at DESC
      ");
          $stmt->bind_param("i", $user_id);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result && $result->num_rows > 0) {
            echo "<table class='display-table'>";
            echo "<thead><tr><th>ID</th><th>Song</th><th>Added At</th></tr></thead><tbody>";
            while ($row = $result->fetch_assoc()) {
              $id = htmlspecialchars($row['fav_id']);
              $song = htmlspecialchars($row['s_name']);
              $added = htmlspecialchars($row['added_at']);
              echo "<tr><td>{$id}</td><td>{$song}</td><td>{$added}</td></tr>";
            }
            echo "</tbody></table>";
          } else {
            echo "<p>No favourite songs yet.</p>";
          }
          $stmt->close();
        } else {
          echo "<p>You must be logged in to view favourites.</p>";
        }
        ?>
      </div>

      <!-- Listening history view shown via sidebar -->
      <div id="history" class="sidebar-tab">
        <h2 class="section-title">History</h2>

        <?php
        if (isset($_SESSION['user_id'])) {
          $user_id = $_SESSION['user_id'];
          $stmt = $mysqli->prepare("
          SELECT h.history_id, s.s_name, h.listened_at
          FROM history h
          JOIN songs s ON h.song_id = s.song_id
          WHERE h.user_id = ?
          ORDER BY h.listened_at DESC
      ");
          $stmt->bind_param("i", $user_id);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result && $result->num_rows > 0) {
            echo "<table class='display-table'>";
            echo "<thead><tr><th>ID</th><th>Song</th><th>Listened At</th></tr></thead><tbody>";
            while ($row = $result->fetch_assoc()) {
              $id = htmlspecialchars($row['history_id']);
              $song = htmlspecialchars($row['s_name']);
              $time = htmlspecialchars($row['listened_at']);
              echo "<tr><td>{$id}</td><td>{$song}</td><td>{$time}</td></tr>";
            }
            echo "</tbody></table>";
          } else {
            echo "<p>No listening history yet.</p>";
          }
          $stmt->close();
        } else {
          echo "<p>You must be logged in to view history.</p>";
        }
        ?>
      </div>


    </div>
  </div>

  <!-- Scripts: AJAX handlers + sidebar interactions -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Reuse one CSRF token for every AJAX POST action in this page.
      const csrfToken = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
      const postForm = data =>
        fetch(location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: new URLSearchParams({ ...data, csrf_token: csrfToken })
        }).then(res => res.text());

      const setupAudioTracking = () => {
        // Record one history row per playback cycle to avoid duplicates.
        document.querySelectorAll('audio').forEach(audio => {
          let recorded = false;

          audio.addEventListener('play', () => {
            if (!recorded) {
              postForm({ record_history: 1, song_id: audio.dataset.songId }).then(console.log);
              recorded = true;
            }
          });

          audio.addEventListener('ended', () => (recorded = false));

          audio.addEventListener('seeked', () => {
            if (audio.currentTime < 1) recorded = false;
          });
        });
      };

      const loadFavourites = () => {
        // Paint favourite hearts based on favourite_list endpoint.
        fetch('?favourite_list=1')
          .then(res => res.json())
          .then(favs => {
            document.querySelectorAll('.fav-icon').forEach(icon => {
              icon.classList.toggle('fav-active', favs.includes(icon.dataset.songId));
            });
          });
      };

      const setupFavouriteToggles = () => {
        // Toggle add/remove favourite and update icon state immediately.
        document.querySelectorAll('.fav-icon').forEach(icon => {
          icon.addEventListener('click', () => {
            const songId = icon.dataset.songId;
            const isFav = icon.classList.contains('fav-active');

            postForm({
              song_id: songId,
              action: isFav ? 'remove' : 'add'
            }).then(response => {
              icon.classList.toggle('fav-active', response === 'added');
            });
          });
        });
      };

      // Initialize page behavior.
      setupAudioTracking();
      loadFavourites();
      setupFavouriteToggles();
    });


    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const icon = document.getElementById('toggle-icon');
      sidebar.classList.toggle('collapsed');
      sidebar.classList.toggle('expanded');
      icon.classList.toggle('fa-angle-double-left');
      icon.classList.toggle('fa-angle-double-right');
    }

    function showSidebarSection(sectionId) {
      document.querySelectorAll('.sidebar-tab').forEach(tab => tab.style.display = 'none');
      document.querySelector('.gallery-container').style.display = 'none';
      document.querySelector('.search-bar-container').style.display = 'none';
      const target = document.getElementById(sectionId);
      if (target) target.style.display = 'block';
    }
  </script>


</body>

</html>