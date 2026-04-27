<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>U-Music</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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
            background: url(../assets/img/Bac.jpg) center/cover no-repeat;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main {
            width: 100%;
            min-height: 100vh;
            flex: 1;
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
            text-shadow: 2px 2px 10px rgba(255, 114, 0, 0.5);
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
            transition: 0.4s ease-in-out;
        }

        .menu ul li a:hover {
            color: #ff7200;
        }

        .logout {
            display: flex;
            align-items: center;
        }

        .logout-btn {
            width: 50px;
            height: 50px;
            background-color: #ff7200;
            border-radius: 50%;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background 0.3s ease;
            overflow: hidden;
        }

        .logout-btn:hover {
            background-color: #fff;
        }

        .logout-btn img {
            width: 45px;
            height: 45px;
        }

        .content {
            max-width: 1200px;
            margin: 30px auto 0;
            color: #fff;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .content-text {
            flex: 1;
            padding-right: 30px;
        }

        .content h1 {
            font-size: 50px;
            letter-spacing: 2px;
        }

        .content span {
            color: #ff7200;
            font-family: 'Transity';
        }

        .content .par {
            margin-top: 20px;
            line-height: 30px;
            letter-spacing: 1.2px;
        }

        .content .cn {
            display: inline-block;
            width: 160px;
            height: 40px;
            background: #ff7200;
            border: none;
            font-size: 18px;
            border-radius: 10px;
            margin-top: 20px;
            cursor: pointer;
            transition: background-color 0.4s ease;
        }

        .content .cn a {
            text-decoration: none;
            color: #000;
            display: block;
            width: 100%;
            height: 100%;
            line-height: 40px;
            text-align: center;
        }

        .cn:hover {
            background-color: #fff;
        }

        .home-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .home-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.5);
        }

        .features {
            padding: 60px 20px;
            background-color: rgba(0, 0, 0, 0.8);
            color: #fff;
            text-align: center;
        }

        .features h2 {
            font-size: 36px;
            margin-bottom: 40px;
            animation: fadeUp 0.8s ease-out;
        }

        .feature-cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .card {
            background: #1f1f1f;
            border-radius: 12px;
            padding: 30px 20px;
            width: 280px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s;
            animation: fadeUp 1s ease-out;
        }

        .card:hover {
            transform: translateY(-10px);
        }

        .card i {
            font-size: 40px;
            color: #ff7200;
            margin-bottom: 15px;
        }

        .card h3 {
            margin: 10px 0;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-text {
            animation: fadeUp 1s ease-out forwards;
            opacity: 0;
        }

        .site-footer {
            background-color: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 40px 20px;
            text-align: center;
            margin-top: auto;
        }

        .site-footer-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 40px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .site-footer-links a {
            color: #ff7200;
            text-decoration: none;
        }

        .site-footer-social a {
            margin: 0 10px;
            color: #ff7200;
        }

        .site-footer-copy {
            margin-top: 10px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }


        @keyframes shine {
            0% {
                background-position: 0% center;
            }

            100% {
                background-position: 200% center;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 12px;
                height: auto;
                text-align: center;
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
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }

            .content {
                flex-direction: column;
                text-align: center;
            }

            .content-text {
                padding-right: 0;
            }

            .home-image {
                margin-top: 20px;
            }

            .content h1 {
                font-size: 2.5rem;
            }

            .features {
                padding: 40px 16px;
            }
        }

        @media (max-width: 480px) {
            .content h1 {
                font-size: 2rem;
            }

            .navbar {
                padding-right: 74px;
            }

            .content .cn {
                width: 100%;
                height: 44px;
            }

            .content .cn a {
                line-height: 44px;
            }

            .feature-cards {
                gap: 16px;
            }

            .card {
                width: 100%;
            }

            .site-footer-links {
                gap: 16px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="main">
        <div class="navbar">
            <h2 class="logo"><span class="logo-u">U</span><span class="logo-music">-Music</span></h2>

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

        <div class="content">
            <div class="content-text">
                <h1>U-Music <br><span>Feel the Music</span></h1>
                <p class="par">
                    Music isn’t just sound—it’s a feeling. Every song here tells a story.<br>
                    Life has a soundtrack—this is mine.<br>
                    Some songs you hear, others you feel. This is where they live.
                </p>
                <button class="cn"><a href="gallery.php">Get Started</a></button>
            </div>

            <div class="home-image">
                <img src="../assets/img/h.jpg" alt="Home Image">
            </div>

        </div>
        <section class="features">
            <h2>Why Choose U-Music?</h2>
            <div class="feature-cards">
                <div class="card">
                    <i class="fas fa-headphones-alt"></i>
                    <h3>Stream Anywhere</h3>
                    <p>Access your favorite music on any device, anytime.</p>
                </div>
                <div class="card">
                    <i class="fas fa-music"></i>
                    <h3>Curated Playlists</h3>
                    <p>Discover mood-based playlists crafted by our team.</p>
                </div>
                <div class="card">
                    <i class="fas fa-user-friends"></i>
                    <h3>Community</h3>
                    <p>Connect with other music lovers and share your taste.</p>
                </div>
            </div>
        </section>

    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>


    <script>
        window.addEventListener("DOMContentLoaded", () => {
            document.querySelector('.content-text').style.opacity = 1;
        });
    </script>

</body>

</html>