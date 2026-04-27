<?php
/**
 * Admin Manage Controller
 *
 * Handles admin-only CRUD operations for users and songs,
 * plus indexing utilities for selected tables.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_admin();

// Enforce CSRF on all admin POST actions (CRUD + indexing).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

// Allow-lists used to validate roles, indexable columns, and target tables.
$valid_roles = ['admin', 'user'];
$allowed_user_columns = ['user_id', 'username', 'pwd', 'email', 'register_date', 'role'];
$allowed_song_columns = ['song_id', 's_name', 'artist', 'added_at', 'path'];
$allowed_fav_columns = ['fav_id', 'user_id', 'song_id', 'added_at'];
$allowed_history_columns = ['history_id', 'user_id', 'song_id', 'listened_at'];
$allowed_tables = ['users', 'songs', 'favourite', 'history'];

$users_error = '';
$songs_error = '';

/**
 * Validate whether a string length is inside the allowed bounds.
 *
 * @param string $value Input value to validate.
 * @param int $min Minimum allowed length.
 * @param int $max Maximum allowed length.
 * @return bool True when length is between min and max.
 */
function is_valid_length(string $value, int $min, int $max): bool
{
    $len = strlen($value);
    return $len >= $min && $len <= $max;
}

/**
 * Build escaped HTML table rows for users list output.
 *
 * @param array<int,array<string,mixed>> $users Users result set.
 * @return string HTML rows ready for table body.
 */
function render_users_rows(array $users): string
{
    $html = '';
    foreach ($users as $user) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars((string) $user['user_id']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $user['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $user['pwd']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $user['email']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $user['register_date']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $user['role']) . '</td>';
        $html .= '</tr>';
    }

    return $html;
}

/**
 * Build escaped HTML table rows for songs list output.
 *
 * @param array<int,array<string,mixed>> $songs Songs result set.
 * @return string HTML rows ready for table body.
 */
function render_songs_rows(array $songs): string
{
    $html = '';
    foreach ($songs as $song) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars((string) $song['song_id']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $song['s_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $song['artist']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $song['added_at']) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) $song['path']) . '</td>';
        $html .= '</tr>';
    }

    return $html;
}

/* ========================= Users: Read ========================= */
$users = [];
if (isset($_GET['action']) && $_GET['action'] === 'display') {
    try {
        $sql = "SELECT user_id, username, pwd, email, register_date, role FROM users";
        $result = $mysqli->query($sql);

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Load users error: ' . $e->getMessage());
        $users_error = 'Unable to load users right now.';
    }
}

/* ========================= Users: Create ======================= */
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'] ?? '';
    $errors = [];

    if (!is_valid_length($username, 3, 50)) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'A valid email address is required.';
    }

    if (!is_valid_length($password, 6, 255)) {
        $errors[] = 'Password must be between 6 and 255 characters.';
    }

    if (!in_array($role, $valid_roles, true)) {
        $errors[] = 'Invalid role selection.';
    }

    if (!empty($errors)) {
        echo "<script>alert('" . addslashes(implode(' ', $errors)) . "'); location.href = location.href;</script>";
    } else {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($password_hash === false) {
                echo "<script>alert('Failed to add user. Please try again.'); location.href = location.href;</script>";
                exit;
            }

            $stmt = $mysqli->prepare("INSERT INTO users (username, email, pwd, role) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $username, $email, $password_hash, $role);
                if ($stmt->execute()) {
                    echo "<script>alert('User added successfully!'); location.href = location.href;</script>";
                } else {
                    echo "<script>alert('Failed to add user. Please try again.'); location.href = location.href;</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to add user. Please try again.'); location.href = location.href;</script>";
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Add user error: ' . $e->getMessage());
            echo "<script>alert('Failed to add user. Please try again.'); location.href = location.href;</script>";
        }
    }
}

/* ========================= Users: Delete ======================= */
if (isset($_POST['delete_user'])) {
    $usernameToDelete = trim($_POST['delete_username']);

    if (!is_valid_length($usernameToDelete, 3, 50)) {
        echo "<script>alert('Please enter a valid username.'); location.href = location.href;</script>";
    } else {
        try {
            $stmt = $mysqli->prepare("DELETE FROM users WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $usernameToDelete);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo "<script>alert('User deleted successfully!'); location.href = location.href;</script>";
                    } else {
                        echo "<script>alert('User not found.'); location.href = location.href;</script>";
                    }
                } else {
                    echo "<script>alert('Failed to delete user. Please try again.'); location.href = location.href;</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to delete user. Please try again.'); location.href = location.href;</script>";
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Delete user error: ' . $e->getMessage());
            echo "<script>alert('Failed to delete user. Please try again.'); location.href = location.href;</script>";
        }
    }
}

/* ========================= Users: Update ======================= */
if (isset($_POST['edit_user'])) {
    $old_username = trim($_POST['old_username']);
    $new_username = trim($_POST['new_username']);
    $new_email = trim($_POST['new_email']);
    $new_password = trim($_POST['new_password']);
    $new_role = $_POST['new_role'] ?? '';
    $errors = [];

    if (!is_valid_length($old_username, 3, 50)) {
        $errors[] = 'Current username is invalid.';
    }

    if (!is_valid_length($new_username, 3, 50)) {
        $errors[] = 'New username must be between 3 and 50 characters.';
    }

    if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL) || strlen($new_email) > 255) {
        $errors[] = 'A valid email address is required.';
    }

    if ($new_password !== '' && !is_valid_length($new_password, 6, 255)) {
        $errors[] = 'Password must be between 6 and 255 characters.';
    }

    if (!in_array($new_role, $valid_roles, true)) {
        $errors[] = 'Invalid role selection.';
    }

    if (!empty($errors)) {
        echo "<script>alert('" . addslashes(implode(' ', $errors)) . "'); location.href = location.href;</script>";
    } else {
        try {
            if ($new_password === '') {
                $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE username = ?");
                if ($stmt) {
                    $stmt->bind_param("ssss", $new_username, $new_email, $new_role, $old_username);
                }
            } else {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                if ($new_password_hash === false) {
                    echo "<script>alert('Failed to update user. Please try again.'); location.href = location.href;</script>";
                    exit;
                }
                $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ?, pwd = ?, role = ? WHERE username = ?");
                if ($stmt) {
                    $stmt->bind_param("sssss", $new_username, $new_email, $new_password_hash, $new_role, $old_username);
                }
            }

            if ($stmt) {
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo "<script>alert('User updated successfully!'); location.href = location.href;</script>";
                    } else {
                        echo "<script>alert('No changes or user not found.'); location.href = location.href;</script>";
                    }
                } else {
                    echo "<script>alert('Failed to update user. Please try again.'); location.href = location.href;</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to update user. Please try again.'); location.href = location.href;</script>";
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Edit user error: ' . $e->getMessage());
            echo "<script>alert('Failed to update user. Please try again.'); location.href = location.href;</script>";
        }
    }
}

/* ========================= Users: Indexing ===================== */
if (isset($_POST['index_users']) && isset($_POST['columns'])) {
    session_start(); // Make sure this is at the top of your file

    $columns = array_values(array_intersect($_POST['columns'], $allowed_user_columns));
    $indexed = [];

    if (empty($columns)) {
        $_SESSION['indexing_msg'] = 'No valid columns selected.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        foreach ($columns as $column) {
            $index_name = "idx_users_" . $column;

            $check = $mysqli->prepare("SHOW INDEX FROM users WHERE Key_name = ?");
            $check->bind_param("s", $index_name);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows === 0) {
                $sql = "CREATE INDEX `$index_name` ON users(`$column`)";
                if ($mysqli->query($sql)) {
                    $indexed[] = $column;
                }
            }

            $check->close();
        }

        if (!empty($indexed)) {
            $_SESSION['indexing_msg'] = 'Indexed columns: ' . implode(", ", $indexed);
        } else {
            $_SESSION['indexing_msg'] = 'All selected columns were already indexed.';
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Index users error: ' . $e->getMessage());
        $_SESSION['indexing_msg'] = 'Indexing failed. Please try again.';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


/* ========================= Songs: Create ======================= */
if (isset($_POST['add_song'])) {
    $s_name = trim($_POST['s_name']);
    $artist = trim($_POST['artist']);
    $path = trim($_POST['path']);

    $errors = [];
    if (!is_valid_length($s_name, 1, 100)) {
        $errors[] = 'Song name must be between 1 and 100 characters.';
    }
    if (!is_valid_length($artist, 1, 100)) {
        $errors[] = 'Artist must be between 1 and 100 characters.';
    }
    if (!is_valid_length($path, 1, 255)) {
        $errors[] = 'Path must be between 1 and 255 characters.';
    }

    if (!empty($errors)) {
        echo "<script>alert('" . addslashes(implode(' ', $errors)) . "'); location.href = location.href;</script>";
    } else {
        try {
            $stmt = $mysqli->prepare("INSERT INTO songs (s_name, artist, path) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $s_name, $artist, $path);
                if ($stmt->execute()) {
                    echo "<script>alert('Song added successfully!'); location.href = location.href;</script>";
                } else {
                    echo "<script>alert('Failed to add song. Please try again.'); location.href = location.href;</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to add song. Please try again.'); location.href = location.href;</script>";
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Add song error: ' . $e->getMessage());
            echo "<script>alert('Failed to add song. Please try again.'); location.href = location.href;</script>";
        }
    }
}


/* ========================= Songs: Read ========================= */
$songs = [];
if (isset($_GET['action']) && $_GET['action'] === 'display' && $_GET['tab'] === 'songs') {
    try {
        $sql = "SELECT song_id, s_name, artist, added_at, path FROM songs";
        $result = $mysqli->query($sql);

        while ($row = $result->fetch_assoc()) {
            $songs[] = $row;
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Load songs error: ' . $e->getMessage());
        $songs_error = 'Unable to load songs right now.';
    }
}

/* ========================= Songs: Update ======================= */
if (isset($_POST['edit_song'])) {
    $old_song_id = trim($_POST['old_song_id']);
    $new_s_name = trim($_POST['new_s_name']);
    $new_artist = trim($_POST['new_artist']);
    $new_path = trim($_POST['new_path']);

    $errors = [];
    if (!is_valid_length($new_s_name, 1, 100)) {
        $errors[] = 'Song name must be between 1 and 100 characters.';
    }
    if (!is_valid_length($new_artist, 1, 100)) {
        $errors[] = 'Artist must be between 1 and 100 characters.';
    }
    if (!is_valid_length($new_path, 1, 255)) {
        $errors[] = 'Path must be between 1 and 255 characters.';
    }

    $song_id = filter_var($old_song_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($song_id === false && !is_valid_length($old_song_id, 1, 100)) {
        $errors[] = 'Please enter a valid song identifier.';
    }

    if (!empty($errors)) {
        echo "<script>alert('" . addslashes(implode(' ', $errors)) . "'); location.href = location.href;</script>";
    } else {
        try {
            if ($song_id !== false) {
                $stmt = $mysqli->prepare("UPDATE songs SET s_name = ?, artist = ?, path = ? WHERE song_id = ?");
                if ($stmt) {
                    $stmt->bind_param("sssi", $new_s_name, $new_artist, $new_path, $song_id);
                }
            } else {
                $stmt = $mysqli->prepare("UPDATE songs SET s_name = ?, artist = ?, path = ? WHERE s_name = ?");
                if ($stmt) {
                    $stmt->bind_param("ssss", $new_s_name, $new_artist, $new_path, $old_song_id);
                }
            }

            if ($stmt) {
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo "<script>alert('Song updated successfully!'); location.href = location.href;</script>";
                    } else {
                        echo "<script>alert('No changes or song not found.'); location.href = location.href;</script>";
                    }
                } else {
                    echo "<script>alert('Failed to update song. Please try again.'); location.href = location.href;</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to update song. Please try again.'); location.href = location.href;</script>";
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Edit song error: ' . $e->getMessage());
            echo "<script>alert('Failed to update song. Please try again.'); location.href = location.href;</script>";
        }
    }
}

/* ========================= Songs: Delete ======================= */
if (isset($_POST['delete_song'])) {
    $song_name_to_delete = trim($_POST['delete_song_name']);

    $song_id = filter_var($song_name_to_delete, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($song_id === false && !is_valid_length($song_name_to_delete, 1, 100)) {
        echo "<script>alert('Please enter a valid song identifier.'); location.href = location.href;</script>";
    } else {
        try {
            if ($song_id !== false) {
                $stmt = $mysqli->prepare("DELETE FROM songs WHERE song_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $song_id);
                }
            } else {
                $stmt = $mysqli->prepare("DELETE FROM songs WHERE s_name = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $song_name_to_delete);
                }
            }

            if ($stmt) {
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo "<script>alert('Song deleted successfully!'); location.href = location.href;</script>";
                    } else {
                        echo "<script>alert('Song not found.'); location.href = location.href;</script>";
                    }
                } else {
                    echo "<script>alert('Failed to delete song. Please try again.'); location.href = location.href;</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to delete song. Please try again.'); location.href = location.href;</script>";
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Delete song error: ' . $e->getMessage());
            echo "<script>alert('Failed to delete song. Please try again.'); location.href = location.href;</script>";
        }
    }
}


/* ========================= Songs: Indexing ===================== */
if (isset($_POST['index_songs']) && isset($_POST['song_columns'])) {
    session_start(); 

    $columns = array_values(array_intersect($_POST['song_columns'], $allowed_song_columns));
    $indexed = [];

    if (empty($columns)) {
        $_SESSION['indexing_msg'] = 'No valid columns selected.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        foreach ($columns as $column) {
            $index_name = "idx_songs_" . $column;

            $check = $mysqli->prepare("SHOW INDEX FROM songs WHERE Key_name = ?");
            $check->bind_param("s", $index_name);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows === 0) {
                $sql = "CREATE INDEX `$index_name` ON songs(`$column`)";
                if ($mysqli->query($sql)) {
                    $indexed[] = $column;
                }
            }

            $check->close();
        }

        if (!empty($indexed)) {
            $_SESSION['indexing_msg'] = 'Indexed song columns: ' . implode(", ", $indexed);
        } else {
            $_SESSION['indexing_msg'] = 'All selected song columns were already indexed.';
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Index songs error: ' . $e->getMessage());
        $_SESSION['indexing_msg'] = 'Indexing failed. Please try again.';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


/* ==================== Favourites/History Indexing ============== */
if (isset($_POST['index_fav']) && isset($_POST['fav_columns'])) {
    session_start(); 

    $columns = array_values(array_intersect($_POST['fav_columns'], $allowed_fav_columns));
    $indexed = [];

    if (empty($columns)) {
        $_SESSION['indexing_msg'] = 'No valid columns selected.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        foreach ($columns as $column) {
            $index_name = "idx_fav_" . $column;

            $check = $mysqli->prepare("SHOW INDEX FROM favourite WHERE Key_name = ?");
            $check->bind_param("s", $index_name);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows === 0) {
                $sql = "CREATE INDEX `$index_name` ON favourite(`$column`)";
                if ($mysqli->query($sql)) {
                    $indexed[] = $column;
                }
            }

            $check->close();
        }

        if (!empty($indexed)) {
            $_SESSION['indexing_msg'] = 'Indexed favourite columns: ' . implode(", ", $indexed);
        } else {
            $_SESSION['indexing_msg'] = 'All selected favourite columns were already indexed.';
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Index favourite error: ' . $e->getMessage());
        $_SESSION['indexing_msg'] = 'Indexing failed. Please try again.';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['index_history']) && isset($_POST['history_columns'])) {
    session_start();

    $columns = array_values(array_intersect($_POST['history_columns'], $allowed_history_columns));
/* ========================= Index Cleanup ======================== */
    $indexed = [];

    if (empty($columns)) {
        $_SESSION['indexing_msg'] = 'No valid columns selected.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        foreach ($columns as $column) {
            $index_name = "idx_history_" . $column;

            $check = $mysqli->prepare("SHOW INDEX FROM history WHERE Key_name = ?");
            $check->bind_param("s", $index_name);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows === 0) {
                $sql = "CREATE INDEX `$index_name` ON history(`$column`)";
                if ($mysqli->query($sql)) {
                    $indexed[] = $column;
                }
            }

            $check->close();
        }

        if (!empty($indexed)) {
            $_SESSION['indexing_msg'] = 'Indexed history columns: ' . implode(", ", $indexed);
        } else {
            $_SESSION['indexing_msg'] = 'All selected history columns were already indexed.';
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Index history error: ' . $e->getMessage());
        $_SESSION['indexing_msg'] = 'Indexing failed. Please try again.';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


if (isset($_POST['drop_index'], $_POST['table'], $_POST['index_name'])) {
    $table = $_POST['table'];
    $index = $_POST['index_name'];

    if (!in_array($table, $allowed_tables, true) || !preg_match('/^idx_[A-Za-z0-9_]+$/', $index)) {
        $_SESSION['indexing_msg'] = 'Invalid index selection.';
        header("Location: ?tab=indexing&action=display");
        exit;
    }

    try {
        $sql = "DROP INDEX `$index` ON `$table`";

        if ($mysqli->query($sql)) {
            $_SESSION['indexing_msg'] = "Dropped index $index from $table.";
        } else {
            $_SESSION['indexing_msg'] = "Failed to drop index $index from $table.";
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Drop index error: ' . $e->getMessage());
        $_SESSION['indexing_msg'] = 'Failed to drop index. Please try again.';
    }

    header("Location: ?tab=indexing&action=display");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage - U-Music Admin</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'Lucy';
            src: url("../assets/fonts/Lucy.ttf") format('truetype');
        }

        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: url(../assets/img/Bac.jpg) no-repeat center center fixed;
            background-size: cover;
            color: white;
        }

        .admin-panel {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 220px;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(6px);
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
        }

        .sidebar h2 {
            font-size: 30px;
            color: #ff7200;
            text-align: center;
            margin-bottom: 40px;
        }

        .sidebar button {
            width: 100%;
            background: none;
            border: none;
            color: #fff;
            padding: 12px 20px;
            margin-bottom: 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar button:hover,
        .sidebar button.active {
            background-color: #ff7200;
            color: black;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .section {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }

        .section.active {
            display: block;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .admin-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-right: 100px;
        }

        .admin-icon {
            width: 50px;
            height: 50px;
        }

        .admin-heading {
            font-size: 30px;
            font-weight: 600;
            color: #fff;
            text-shadow: 1px 1px 6px black;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 48px;
            font-weight: 700;
        }

        .logo-u {
            font-family: 'Lucy';
            font-size: 70px;
            color: rgb(233, 36, 36);
            margin-right: 6px;
            text-shadow: 2px 2px 10px rgba(255, 207, 0, 0.8);
        }

        .logo-music {
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

        .logout-btn {
            width: 50px;
            height: 50px;
            background-color: #ff7200;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .logout-btn img {
            width: 45px;
            height: 45px;
            pointer-events: none;
        }

        .logout-btn:hover {
            background-color: #fff;
        }

        .home-button-container {
            margin-top: 30px;
        }

        .home-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #ff7200;
            color: black;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            font-size: 18px;
            transition: background 0.3s ease;
        }

        .home-button:hover {
            background-color: rgb(255, 106, 0);
        }

        .admin-image-container {
            text-align: left;
            margin: 20px 0;
        }

        .admin-image {
            width: 700px;
            height: 360px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        .admin-image:hover {
            transform: scale(1.05);
            
        }

        .user-actions {
            display: flex;
            gap: 30px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .action-tile {
            width: 100px;
            height: 100px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .action-tile:hover {
            background-color: #ff7200;
            color: black;
        }

        .action-tile i {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .users-table th,
        .users-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            color: white;
        }

        .users-table th {
            background-color: #ff7200;
            color: black;
        }

        .songs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .songs-table th,
        .songs-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            color: white;
        }

        .songs-table th {
            background-color: #ff7200;
            color: black;
        }

        .songs-table tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .songs-table tr:hover {
            background-color: rgba(255, 114, 0, 0.5);

        }

        .popup-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(6px);
            border: 2px solid white;
            padding: 30px;
            border-radius: 1rem;
            z-index: 1000;
            width: 320px;
            color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
        }

        .popup-form h2 {
            margin-bottom: 20px;
            color: #ff7200;
            text-align: center;
            font-size: 24px;
        }

        .popup-form label {
            display: block;
            margin: 10px 0;
            font-size: 16px;
        }

        .popup-form input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }

        .popup-form button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            border: none;
            background-color: #ff7200;
            color: black;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .popup-form button:hover {
            background-color: white;
        }

        .popup-form #close-popup {
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 20px;
            color: #ff7200;
            cursor: pointer;
        }

        #add-user-form,
        #delete-user-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(65, 61, 61, 0.7);
            border: 2px solid white;
            padding: 2rem;
            border-radius: 1rem;
            backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            color: white;
            width: 25%;
            box-sizing: border-box;
        }

        #add-user-form input,
        #add-user-form select,
        #delete-user-form input {
            width: 100%;
            padding: 0.5rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            border: 1px solid #ff7200;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 16px;
            outline: none;
        }

        #add-user-form select {
            color: black;
            background: rgba(170, 168, 168, 0.1);
        }

        #add-user-form button,
        #delete-user-form button {
            width: 100%;
            padding: 1rem;
            background-color: white;
            color: black;
            border-radius: 8px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #add-user-form button:hover,
        #delete-user-form button:hover {
            background-color: rgb(182, 94, 67);
        }

        #close-popup {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #ff7200;
        }

        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 200vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }

        #edit-user-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(65, 61, 61, 0.7);
            border: 2px solid white;
            padding: 2rem;
            border-radius: 1rem;
            backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            color: white;
            width: 25%;
            box-sizing: border-box;
        }

        #edit-user-form input,
        #edit-user-form select {
            width: 100%;
            padding: 0.5rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            border: 1px solid #ff7200;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 16px;
            outline: none;
        }

        #edit-user-form select {
            color: black;
            background: rgba(170, 168, 168, 0.1);
        }

        #edit-user-form button {
            width: 100%;
            padding: 1rem;
            background-color: white;
            color: black;
            border-radius: 8px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #edit-user-form button:hover {
            background-color: rgb(182, 94, 67);
        }

        #overlay {
            display: none;
            /* Ensure the overlay is hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        #add-song-form,
        #edit-song-form,
        #delete-song-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(65, 61, 61, 0.7);
            border: 2px solid white;
            padding: 2rem;
            border-radius: 1rem;
            backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            color: white;
            width: 30%;
            /* Controlled width */
            box-sizing: border-box;
            min-width: 300px;
        }

        #add-song-form input,
        #add-song-form button,
        #edit-song-form input,
        #edit-song-form button,
        #delete-song-form input,
        #delete-song-form button {
            width: 100%;
            padding: 0.5rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            border: 1px solid #ff7200;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 16px;
            outline: none;
        }

        #add-song-form button,
        #edit-song-form button,
        #delete-song-form button {
            background-color: #ff7200;
            color: black;
            font-weight: bold;
            cursor: pointer;
        }

        #add-song-form button:hover,
        #edit-song-form button:hover,
        #delete-song-form button:hover {
            background-color: rgb(182, 94, 67);
        }

        #close-song-popup {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #ff7200;
        }

        .drop-btn {
            background-color: #e63946;
            border: none;
            padding: 6px 12px;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .drop-btn:hover {
            background-color: #c7212f;
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

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .table-wrapper table {
            min-width: 600px;
        }

        @keyframes shine {
            0% {
                background-position: 0% center;
            }

            100% {
                background-position: 200% center;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .admin-panel {
                flex-direction: column;
                height: auto;
            }

            .sidebar {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .sidebar h2 {
                width: 100%;
                margin-bottom: 20px;
            }

            .sidebar button {
                width: auto;
            }

            .navbar {
                flex-direction: column;
                align-items: center;
                gap: 12px;
                position: relative;
                padding-right: 84px;
            }

            .navbar .logout-btn {
                position: absolute;
                top: 0;
                right: 20px;
            }

            .admin-header {
                padding-right: 0;
            }

            .main-content {
                padding: 20px;
            }

            .admin-image {
                width: 100%;
                height: auto;
            }
        }

        @media (max-width: 480px) {
            .admin-heading {
                font-size: 22px;
            }

            .navbar {
                padding-right: 74px;
            }

            .action-tile {
                width: 80px;
                height: 80px;
            }

            .popup-form,
            #add-user-form,
            #delete-user-form,
            #edit-user-form {
                width: 90%;
            }

            .home-button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="admin-panel">
        <div class="sidebar">
            <h2>Manage</h2>
            <button class="tab-button" onclick="showSection('users', this)">Manage Users</button>
            <button class="tab-button" onclick="showSection('songs', this)">Manage Songs</button>
            <button class="tab-button" onclick="showSection('indexing', this)">Indexing</button>

        </div>

        <div class="main-content">
            <div class="navbar">
                <a href="manage.php" class="logo" style="text-decoration: none;">
                    <span class="logo-u">U</span><span class="logo-music">-Music</span>
                </a>

                <div class="admin-header">
                    <img src="https://img.icons8.com/?size=100&id=enhXU0wuHL8s&format=png&color=000000" alt="Admin Icon"
                        class="admin-icon">
                    <h2 class="admin-heading">Admin Panel</h2>
                </div>
                <a href="/Project/pages/logout.php" class="logout-btn" title="Logout">
                    <img src="../assets/img/logout.png" alt="Logout">
                </a>
            </div>

            <!-- Welcome section shown by default before selecting admin actions -->
            <div id="welcome" class="section active">
                <h2 style="font-size: 40px;">Welcome Admin!</h2>
                <p style="font-size: 20px;">Select an option from the sidebar to manage users or songs.</p>

                <div class="admin-image-container">
                    <img src="../assets/img/admin.jpg" alt="Admin Welcome Image" class="admin-image">
                </div>

                <div class="home-button-container">
                    <a href="home.php" class="home-button">Visit Website</a>
                </div>
            </div>

            <!-- Users management section (CRUD + display) -->
            <div id="users" class="section">
                <h2>Manage Users</h2>
                <div class="user-actions">
                    <div class="action-tile" onclick="openForm('add-user-form')">
                        <i class="fas fa-user-plus"></i><span>Add</span>
                    </div>
                    <div class="action-tile" onclick="openForm('delete-user-form')">
                        <i class="fas fa-user-minus"></i><span>Delete</span>
                    </div>
                    <div class="action-tile" onclick="openForm('edit-user-form')">
                        <i class="fas fa-user-edit"></i><span>Alter</span>
                    </div>
                    <div onclick="window.location.href='?action=display&tab=users'" class="action-tile">
                        <i class="fas fa-users"></i><span>Display</span>
                    </div>
                </div>

                <?php if ($users_error !== ''): ?>
                    <p><?php echo htmlspecialchars($users_error); ?></p>
                <?php elseif (!empty($users)): ?>
                    <div class="table-wrapper">
                        <table class="display-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Password</th>
                                    <th>Email</th>
                                    <th>Register Date</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo render_users_rows($users); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Songs management section (CRUD + display) -->
            <div id="songs" class="section">
                <h2>Manage Songs</h2>
                <div class="user-actions">
                    <div class="action-tile" onclick="openForm('add-song-form')">
                        <i class="fas fa-music"></i><span>Add Song</span>
                    </div>

                    <div id="add-song-form" style="display: none;">
                        <span id="close-song-popup" onclick="closeForm('add-song-form')"
                            style="float: right; cursor: pointer;">&times;</span>
                        <h2>Add New Song</h2>
                        <form method="post">
                            <?php echo csrf_field(); ?>
                            <input type="text" name="s_name" placeholder="Song Name" required>
                            <input type="text" name="artist" placeholder="Artist" required>
                            <input type="text" name="path" placeholder="Path/URL" required>
                            <button type="submit" name="add_song">Add Song</button>
                        </form>
                    </div>


                    <div class="action-tile" onclick="openForm('delete-song-form')">
                        <i class="fas fa-trash"></i><span>Delete Song</span>
                    </div>
                    <div class="action-tile" onclick="openForm('edit-song-form')">
                        <i class="fas fa-pen"></i><span>Edit Song</span>
                    </div>
                    <div class="action-tile" onclick="window.location.href='?action=display&tab=songs'">
                        <i class="fas fa-eye"></i><span>Display Songs</span>
                    </div>
                </div>

                <?php if (isset($_GET['action']) && $_GET['action'] === 'display' && $_GET['tab'] === 'songs' && $songs_error !== ''): ?>
                    <p><?php echo htmlspecialchars($songs_error); ?></p>
                <?php elseif (isset($_GET['action']) && $_GET['action'] === 'display' && $_GET['tab'] === 'songs' && !empty($songs)): ?>
                    <div class="table-wrapper">
                        <table class="songs-table">
                            <thead>
                                <tr>
                                    <th>Song ID</th>
                                    <th>Song Name</th>
                                    <th>Artist</th>
                                    <th>Added At</th>
                                    <th>Path</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo render_songs_rows($songs); ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif (isset($_GET['action']) && $_GET['action'] === 'display' && $_GET['tab'] === 'songs'): ?>
                    <p>No songs found.</p>
                <?php endif; ?>

            </div>
            <!-- Indexing dashboard section for DB performance helpers -->
            <div id="indexing" class="section">
                <h2>Indexing Dashboard</h2>
                <div class="user-actions">
                    <div class="action-tile" onclick="openForm('index-users-form')">
                        <i class="fas fa-user"></i>
                        <span>Users</span>

                    </div>
                    <div class="action-tile" onclick="openForm('index-songs-form')">
                        <i class="fas fa-music"></i>
                        <span>Songs</span>
                    </div>
                    <div class="action-tile" onclick="openForm('index-fav-form')">
                        <i class="fas fa-star"></i>
                        <span>Favourites</span>
                    </div>
                    <div class="action-tile" onclick="openForm('index-history-form')">
                        <i class="fas fa-clock"></i>
                        <span>History</span>
                    </div>
                    <div class="action-tile" onclick="window.location.href='?tab=indexing&action=display'">
                        <i class="fas fa-table"></i>
                        <span>View Indexes</span>
                    </div>

                </div>
                <?php if (isset($_GET['action']) && $_GET['action'] === 'display' && $_GET['tab'] === 'indexing'): ?>
                    <div style="margin-top: 30px;">
                        <h3 style="color: #ff7200;">Indexed Columns Overview</h3>

                        <div class="table-wrapper">
                            <table class="display-table">
                                <thead>
                                    <tr>
                                        <th>Table Name</th>
                                        <th>Indexed Column</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // Inspect each managed table and print only custom idx_* indexes.
                                $tables = ['users', 'songs', 'favourite', 'history'];
                                $indexedCount = 0;

                                // Outer loop: per table; inner loop: per index returned by SHOW INDEX.
                                foreach ($tables as $table) {
                                    $result = $mysqli->query("SHOW INDEX FROM `$table`");

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $key = $row['Key_name'];
                                            $column = $row['Column_name'];

                                            if (stripos($key, 'idx_') === 0) {
                                                echo "<tr>
                                <td style='text-transform: capitalize;'>" . htmlspecialchars($table) . "</td>
                                <td>" . htmlspecialchars($column) . "</td>
                                <td>
                                                                    <form method='post' style='display:inline;'>" . csrf_field() . "
                                    <input type='hidden' name='drop_index' value='1'>
                                    <input type='hidden' name='table' value='" . htmlspecialchars($table) . "'>
                                    <input type='hidden' name='index_name' value='" . htmlspecialchars($key) . "'>
                                    <button type='submit' class='drop-btn' onclick='return confirm(\"Drop index $key from $table?\")'>Drop</button>
                                  </form>
                                </td>
                              </tr>";
                                                $indexedCount++;
                                            }
                                        }
                                    }
                                }

                                if ($indexedCount === 0) {
                                    echo "<tr><td colspan='3' style='text-align: center;'>No custom indexes found.</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>




            </div>
        </div>

        <!-- Index Users Modal -->
        <div id="index-users-form" class="popup-form" style="display: none;">
            <span id="close-popup" onclick="closeForms()" style="float: right; cursor: pointer;">×</span>
            <h2>Index Users Table</h2>
            <form method="post">
                <?php echo csrf_field(); ?>
                <label><input type="checkbox" name="columns[]" value="user_id"> user_id</label><br>
                <label><input type="checkbox" name="columns[]" value="username"> username</label><br>
                <label><input type="checkbox" name="columns[]" value="pwd"> pwd</label><br>
                <label><input type="checkbox" name="columns[]" value="email"> email</label><br>
                <label><input type="checkbox" name="columns[]" value="register_date"> register_date</label><br>
                <label><input type="checkbox" name="columns[]" value="role"> role</label><br><br>
                <button type="submit" name="index_users">Run Indexing</button>
            </form>
        </div>

        <!-- Index Songs Modal -->
        <div id="index-songs-form" class="popup-form" style="display: none;">
            <span id="close-popup" onclick="closeForms()" style="float: right; cursor: pointer;">×</span>
            <h2>Index Songs Table</h2>
            <form method="post">
                <?php echo csrf_field(); ?>
                <label><input type="checkbox" name="song_columns[]" value="song_id"> song_id</label><br>
                <label><input type="checkbox" name="song_columns[]" value="s_name"> s_name</label><br>
                <label><input type="checkbox" name="song_columns[]" value="artist"> artist</label><br>
                <label><input type="checkbox" name="song_columns[]" value="added_at"> added_at</label><br>
                <label><input type="checkbox" name="song_columns[]" value="path"> path</label><br><br>
                <button type="submit" name="index_songs">Run Indexing</button>
            </form>
        </div>

        <!-- Index Favourites Modal -->
        <div id="index-fav-form" class="popup-form" style="display: none;">
            <span id="close-popup" onclick="closeForms()" style="float: right; cursor: pointer;">×</span>
            <h2>Index Favourite Table</h2>
            <form method="post">
                <?php echo csrf_field(); ?>
                <label><input type="checkbox" name="fav_columns[]" value="fav_id"> fav_id</label><br>
                <label><input type="checkbox" name="fav_columns[]" value="user_id"> user_id</label><br>
                <label><input type="checkbox" name="fav_columns[]" value="song_id"> song_id</label><br>
                <label><input type="checkbox" name="fav_columns[]" value="added_at"> added_at</label><br><br>
                <button type="submit" name="index_fav">Run Indexing</button>
            </form>
        </div>

        <!-- Index History Modal -->
        <div id="index-history-form" class="popup-form" style="display: none;">
            <span id="close-popup" onclick="closeForms()" style="float: right; cursor: pointer;">×</span>
            <h2>Index History Table</h2>
            <form method="post">
                <?php echo csrf_field(); ?>
                <label><input type="checkbox" name="history_columns[]" value="history_id"> history_id</label><br>
                <label><input type="checkbox" name="history_columns[]" value="user_id"> user_id</label><br>
                <label><input type="checkbox" name="history_columns[]" value="song_id"> song_id</label><br>
                <label><input type="checkbox" name="history_columns[]" value="listened_at"> listened_at</label><br><br>
                <button type="submit" name="index_history">Run Indexing</button>
            </form>
        </div>






    </div>
    </div>

    <div id="overlay" onclick="closeForms()"></div>

    <!-- Delete Song Form -->
    <div id="delete-song-form" style="display: none;">
        <span id="close-song-popup" onclick="closeForms()" style="float: right; cursor: pointer;">&times;</span>
        <h2>Delete Song</h2>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="text" name="delete_song_name" id="delete-song-name" placeholder="Enter Song Name" required>
            <p>Are you sure you want to delete this song?</p>
            <button type="submit" name="delete_song">Delete Song</button>
        </form>
    </div>

    <!-- Edit Song Form -->
    <div id="edit-song-form" style="display: none;">
        <span id="close-song-popup" onclick="closeForms()" style="float: right; cursor: pointer;">&times;</span>
        <h2>Edit Song</h2>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="text" name="old_song_id" id="edit-song-id" placeholder="Old Song Name" required>
            <input type="text" name="new_s_name" placeholder="New Song Name" required>
            <input type="text" name="new_artist" placeholder="New Artist" required>
            <input type="text" name="new_path" placeholder="New Path/URL" required>
            <button type="submit" name="edit_song">Update Song</button>
        </form>
    </div>

    <!-- Add User Form -->
    <div id="add-user-form">
        <span id="close-popup" onclick="closeForms()">X</span>
        <h2>Add New User</h2>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <button type="submit" name="add_user">Add User</button>
        </form>
    </div>

    <!-- Delete User Form -->
    <div id="delete-user-form">
        <span id="close-popup" onclick="closeForms()">X</span>
        <h2>Delete User</h2>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="text" name="delete_username" placeholder="Enter Username to Delete" required>
            <button type="submit" name="delete_user">Delete User</button>
        </form>
    </div>

    <input type="hidden" id="selected-tab"
        value="<?php echo isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : ''; ?>">

    <!-- Index Users Popup -->
    <div id="index-users-form" style="display: none;">
        <span id="close-popup" onclick="closeForms()" style="float: right; cursor: pointer;">×</span>
        <h2>Index Users Table</h2>
        <form method="post">
            <?php echo csrf_field(); ?>
            <label><input type="checkbox" name="columns[]" value="user_id"> user_id</label><br>
            <label><input type="checkbox" name="columns[]" value="username"> username</label><br>
            <label><input type="checkbox" name="columns[]" value="pwd"> pwd</label><br>
            <label><input type="checkbox" name="columns[]" value="email"> email</label><br>
            <label><input type="checkbox" name="columns[]" value="register_date"> register_date</label><br>
            <label><input type="checkbox" name="columns[]" value="role"> role</label><br><br>
            <button type="submit" name="index_users">Run Indexing</button>
        </form>
    </div>

    <!-- Edit (Alter) User Form -->
    <div id="edit-user-form">
        <span id="close-popup" onclick="closeForms()">X</span>
        <h2>Edit User</h2>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="text" name="old_username" placeholder="Current Username" required>
            <input type="text" name="new_username" placeholder="New Username" required>
            <input type="email" name="new_email" placeholder="New Email" required>
            <input type="password" name="new_password" placeholder="New Password (optional)">
            <select name="new_role" required>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <button type="submit" name="edit_user">Update User</button>
        </form>
    </div>
    <?php
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    if (isset($_SESSION['indexing_msg'])) {
        echo "<script>alert('" . addslashes($_SESSION['indexing_msg']) . "');</script>";
        unset($_SESSION['indexing_msg']); 
    }
    ?>

    <script>
    function showSection(sectionId, button) {
        console.log('Showing section:', sectionId);

        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show the selected section and activate the button
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');
        }

        if (button) {
            button.classList.add('active');
        }

        // Close any open forms
        closeForms();
    }

    function openForm(formId) {
        console.log('Opening form:', formId);
        closeForms(); // Close any open forms first

        document.getElementById('overlay').style.display = 'block';

        const form = document.getElementById(formId);
        if (form) {
            form.style.display = 'block';
        }
    }

    function closeForms() {
        document.getElementById('overlay').style.display = 'none';

        // Hide all popup forms
        const allForms = document.querySelectorAll('[id$="-form"]');
        allForms.forEach(form => {
            if (form.id !== 'welcome') { // Don't hide the welcome section
                form.style.display = 'none';
            }
        });
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Set up overlay click handler
        document.getElementById('overlay').addEventListener('click', closeForms);

        // Handle URL parameters for tab selection
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        const action = urlParams.get('action');

        if (tab) {
            // Find and activate the corresponding tab
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => {
                if (button.textContent.toLowerCase().includes(tab.toLowerCase())) {
                    showSection(tab, button);
                }
            });
        }

        // If action is display, show the appropriate section
        if (action === 'display' && tab) {
            showSection(tab, document.querySelector('.tab-button.active'));
        }
    });
    </script>

</body>

</html>
