# CineEclipse - Movie CMS

CineEclipse is a PHP-based movie catalog and admin management web app built for browsing movies, searching by title/year/genre, and managing records through an admin dashboard. It uses MySQL as the database and is designed for local development with XAMPP.

## Project Background

This project was inspired by the idea of creating a dynamic, user-contributed movie database for films that are unavailable through mainstream movie databases in China. The goal is to allow users to create, update, and manage movie information through a content management system.

The current version is an initial prototype using a limited set of sample movie data. Future iterations will improve the interface and expand the functionality before real-world data is introduced. The timeline for these updates has not yet been determined.

## Features

- Browse a movie catalog on the home page
- Search movies by title, release year, and genre
- View detailed movie pages with metadata and poster links
- User registration and login
- Admin-only backstage dashboard for:
  - managing users
  - managing movies
  - managing genres
- Secure password hashing via PHP password handling

## Tech Stack

- PHP
- MySQL / MariaDB
- Apache via XAMPP
- HTML, CSS, JavaScript
- Bootstrap for styling

## Screenshots

<img width="3024" height="2720" alt="Project2" src="https://github.com/user-attachments/assets/31b993cd-d32e-4b5e-9830-f5e6066186a8" />

## Project Structure

- `index.php` – home page and movie search
- `movie_detail.php` – detailed movie page
- `login.php` – login form
- `register.php` – user registration form
- `backstage.php` – admin dashboard
- `connect.php` – database connection setup
- `header.php` – site navigation/header
- `CRUD/` – create, edit, and delete movie functionality
- `user_management/` – user CRUD operations
- `genre_management/` – genre management pages
- `style.css` – core styling
- `script.js` – client-side interactions

## Prerequisites

- XAMPP installed and running
- Apache and MySQL enabled in XAMPP
- PHP 7+ or 8+
- Web browser

## Local Setup

1. Start Apache and MySQL in XAMPP.
2. Place this project folder inside the XAMPP web root, usually:
   - Windows: `C:/xampp/htdocs/`
   - macOS/Linux: `/Applications/XAMPP/xamppfiles/htdocs/`
3. Open the project in your browser:
   - `http://localhost/WebDev2/CineEclipse/`

## Database Setup

This project expects a MySQL database named `movie_cms`.

1. Open phpMyAdmin in XAMPP.
2. Create a database named `movie_cms`.
3. Ensure the database contains the following tables used by the app:
   - `Users`
   - `Movies`
   - `Genres`

The connection details are defined in `connect.php`:

- Database name: `movie_cms`
- Username: `root`
- Password: empty string
- Host: `localhost`

If your local MySQL setup uses different credentials, update the constants in `connect.php` before running the app.

## Admin Access

The app checks for a user role named `admin` in the `Users` table. Admin users can access the backstage dashboard and manage content.

## Typical User Flow

1. Register a new user account
2. Log in with the registered email and password
3. Browse or search movie entries from the home page
4. Admin can go to the backstage page to manage users, movies, and genres

## Notes

- The app uses session-based authentication.
- Some pages require the user to be logged in and/or have admin privileges.
- If the database is not initialized correctly, pages will fail when attempting to fetch or write records.

## License

This project is a simple movie recommendation website I developed as a CMS project for my Web Development 2 course.
