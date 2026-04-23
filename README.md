# U-Music

A PHP-based music streaming and management website. This project allows users to browse a music gallery, register/login, and manage content via an admin dashboard.

## Features

- **User Authentication**: Secure Login and Registration system.
- **Admin Dashboard**: Access via `Manage.php` for administrators to manage the site content.
- **Gallery**: Browse and view music collections in `Gallery.php`.
- **Responsive Navigation**: A clean sidebar and navbar for easy access to Home, About, Contact, and Gallery pages.
- **Custom Fonts**: Utilizes custom typography like 'Lucy' and 'Transity'.

## Project Structure

 `index.php`: Entry point (login page).
 `pages/`: All PHP pages (Home, Gallery, About, Contact, Register, Manage, Wellcome).
- `includes/`: Shared PHP includes (database connection).
  - Based on `index.php`, the default connection uses:
- `img/gallery/`: Gallery images.
  - Visit `http://localhost:8000/` in your browser.
  - Direct access to pages is still available, e.g. `http://localhost:8000/pages/home.php`.
- `music/`: MP3 audio files.
- `uploads/`: Reserved for future uploads.

## Prerequisites

- **PHP**: Recommended version 7.x or higher.
- **MySQL/MariaDB**: The project uses a database named `u-music`.
- **Web Server**: Apache or any PHP-compatible server (like the PHP built-in server).

## Setup Instructions

1.  **Database Configuration**:
    - Ensure you have MySQL running.
    - Create a database named `u-music`.
    - Based on `index.php`, the default connection uses:
      - Host: `localhost`
      - User: `root`
      - Password: `1234`
    - You will need a `users` table with at least `username`, `pwd`, and `role` columns.

2.  **Running Locally**:
    - Open your terminal and navigate to the project root:
      ```bash
      cd /home/uzair/web/Project
      ```
    - Start the PHP built-in server:
      ```bash
      php -S localhost:8000
      ```
    - Visit `http://localhost:8000/` in your browser.
    - Direct access to pages is still available, e.g. `http://localhost:8000/pages/home.php`.

## Tech Stack

- **Backend**: PHP
- **Frontend**: HTML5, CSS3 (with custom animations and Flexbox)
- **Database**: MySQL
- **Icons**: Font Awesome 6.0.0

## File Structure

```text
Project/
├── index.php
├── README.md
├── fonts/
│   ├── BeautifulDream.otf
│   ├── BlackoutOldskull.ttf
│   ├── Gafiya.otf
│   ├── Lucy.ttf
│   ├── Themunday.ttf
│   └── Transcity.otf
├── img/
│   ├── Background.jpg
│   ├── admin.jpg
│   ├── div.jpg
│   ├── h.jpg
│   ├── image.png
│   ├── logo.png
│   ├── logout.png
│   ├── man.jpg
│   ├── gallery/
│   │   └── 1.jpg
│   └── song_thumbs/
│       ├── Easy Travel.jpg
│       ├── Flower Field.jpg
│       ├── Funny Day.jpg
│       ├── Funny Kids.jpg
│       ├── Happy.jpg
│       ├── Jazz Lounge.jpg
│       ├── Morning Coffee.jpg
│       ├── Soft Calm.jpg
│       ├── The Inspiring Ambient.jpg
│       ├── The Travel.jpg
│       └── Visionary.jpg
├── includes/
│   └── db.php
├── music/
│   ├── Easy Travel.mp3
│   ├── Flower Field.mp3
│   ├── Funny Day.mp3
│   ├── Funny Kids.mp3
│   ├── Happy.mp3
│   ├── Jazz Lounge.mp3
│   ├── Morning Coffee.mp3
│   ├── Soft Calm.mp3
│   ├── The Inspiring Ambient.mp3
│   ├── The Travel.mp3
│   └── Visionary.mp3
├── pages/
│   ├── about.php
│   ├── contact.php
│   ├── gallery.php
│   ├── home.php
│   ├── manage.php
│   ├── register.php
│   └── wellcome.php
└── uploads/
```
