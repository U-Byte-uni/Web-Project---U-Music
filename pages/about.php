<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - U-Music</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

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

        /* ── Navbar (identical to Contact) ── */
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

        /* ── About wrapper mirrors contact-wrapper ── */
        .about-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 40px 0;
            gap: 40px;
            margin-top: 20px;
        }

        /* ── Left card mirrors contact-container ── */
        .about-container {
            flex: 1;
            padding: 40px;
            background-color: rgba(0, 0, 0, 0.26);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(255, 114, 0, 0.3);
            backdrop-filter: blur(5px);
        }

        .about-container h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #ff7200;
            font-size: 36px;
        }

        .about-container p {
            font-size: 17px;
            line-height: 1.9;
            letter-spacing: 0.4px;
        }

        /* ── Right column mirrors contact-right ── */
        .about-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
        }

        .image-box {
            width: 300px;
            height: 100%;
            min-height: 400px;
            background-image: url('../img/man.jpg');
            background-size: cover;
            background-position: center top;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        /* ── Social icons (identical to Contact) ── */
        .social-icons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .social-icons a {
            font-size: 28px;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .social-icons a:hover {
            transform: scale(1.2);
        }

        .social-icons .facebook  { color: #3b5998; }
        .social-icons .x-twitter { color: #ffffff; }
        .social-icons .instagram { color: #e4405f; }
        .social-icons .youtube   { color: #FF0000; }
        .social-icons .discord   { color: #5865F2; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .about-wrapper {
                flex-direction: column-reverse;
                align-items: center;
                padding: 20px 0;
            }

            .image-box {
                width: 100%;
                max-width: 300px;
                height: 400px;
            }

            .about-container {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
                height: auto;
            }

            .menu ul {
                flex-direction: column;
                gap: 10px;
            }
        }

        @keyframes shine {
            0%   { background-position: 0% center; }
            100% { background-position: 200% center; }
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
                    <?php if (isset($_SESSION['username']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="manage.php">Manage</a></li>
                    <?php endif; ?>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
            </div>
            <a href="/" class="logout-btn" title="Logout">
                <img src="../img/logout.png" alt="Logout">
            </a>
        </div>

        <div class="about-wrapper">

            <!-- Left: text card -->
            <div class="about-container">
                <h1>About U-Music</h1>
                <p>
                    U-Music is more than a platform—it's a passion. Born from the love of melodies, rhythm, and soul,
                    U-Music brings together artists and listeners into a shared space of musical expression.<br><br>
                    Our mission is to make music discovery and sharing effortless, fun, and inspiring. Whether you're a
                    creator or a fan, this is where your musical journey gets louder.<br><br>
                    From beats that lift your mood to lyrics that speak your truth, U-Music celebrates the universal
                    language of music—one beat at a time.
                </p>
            </div>

            <!-- Right: portrait + social icons -->
            <div class="about-right">
                <div class="image-box"></div>
                <div class="social-icons">
                    <a href="#" class="facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="x-twitter"><i class="fab fa-x-twitter"></i></a>
                    <a href="#" class="instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="youtube"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="discord"><i class="fab fa-discord"></i></a>
                </div>
            </div>

        </div>
    </div>

</body>

</html>