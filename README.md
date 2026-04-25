# U-Music

A PHP-based music streaming and management website. This project allows users to browse a music gallery, register/login, and manage content via an admin dashboard.

## Features

- **User Authentication**: Secure Login and Registration system (passwords are hashed; legacy plaintext passwords are upgraded on successful login).
- **Admin Dashboard**: Access via `Manage.php` for administrators to manage the site content.
- **Gallery**: Browse and view music collections in `Gallery.php`.
- **Responsive Navigation**: A clean sidebar and navbar for easy access to Home, About, Contact, and Gallery pages.
- **Custom Fonts**: Utilizes custom typography like 'Lucy' and 'Transity'.

## Project Structure

- index.php: Entry point (login page).
- pages/: All PHP pages (Home, Gallery, About, Contact, Register, Manage, Wellcome, Profile, Logout).
- includes/: Shared PHP includes (db.php, auth.php, csrf.php).
- database/schema.sql: Contact form table schema.
- img/: Images and UI assets (gallery/ and song_thumbs/).
- music/: MP3 audio files.
- uploads/: Reserved for future uploads.

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
    - Required tables:
      - users: user_id, username, pwd, email, role, register_date
      - songs: song_id, s_name, artist, added_at, path
      - favourite: fav_id, user_id, song_id, added_at
      - history: history_id, user_id, song_id, listened_at
      - contact_messages: id, name, email, message, created_at
    - Contact form storage uses the `contact_messages` table (see [database/schema.sql](database/schema.sql)).

2.  **Contact Messages Table**:
    - Run the SQL in [database/schema.sql](database/schema.sql) to create `contact_messages`.

3.  **Default Admin Creation**:
    - Register with username `admin` to create an admin account automatically.
    - Alternatively, set role = 'admin' for an existing user in the users table.

4.  **Running Locally**:
    - Open your terminal and navigate to the project root:
      ```bash
      cd /home/uzair/web/Project
      ```
    - Start the PHP built-in server:
      ```bash
      php -S localhost:8000
      ```
    - Visit `http://localhost:8000/` in your browser.

## Security Notes

- Passwords are hashed on registration and verified on login.
- Prepared statements are used for writes in admin CRUD and profile updates.
- CSRF tokens protect all state-changing POST forms.

## Tech Stack

- **Backend**: PHP
- **Frontend**: HTML5, CSS3 (with custom animations and Flexbox)
- **Database**: MySQL
- **Icons**: Font Awesome 6.0.0

## File Structure

```text
Project/
├── database/
│   └── schema.sql
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
│   ├── auth.php
│   ├── csrf.php
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
│   ├── logout.php
│   ├── manage.php
│   ├── profile.php
│   ├── register.php
│   └── wellcome.php
└── uploads/
```
