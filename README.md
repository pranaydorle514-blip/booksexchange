# BookSwap – Second-Hand Book Marketplace

Source project for the [booksexchange GitHub repository](https://github.com/pranaydorle514-blip/booksexchange).

A PHP + MySQL marketplace for students to list, browse, and request second-hand books.

## Features
- Student registration and login with hashed email/password credentials
- Browse, search, and filter book listings
- Explore Open Library catalog results
- Add listings with branch, semester, condition, price, and exchange option
- Send buy or exchange requests and accept or decline incoming requests
- Optional seller email notifications when SMTP is configured
- Responsive desktop and mobile layout
- MySQL persistence using PDO prepared statements

## Run from the GitHub repository on Windows

1. Install XAMPP and GitHub Desktop.
2. In GitHub Desktop, choose **File → Clone repository**, open the **URL** tab, enter `https://github.com/pranaydorle514-blip/booksexchange.git`, and clone it to `C:\xampp\htdocs\BookSwap`. Alternatively, run `git clone https://github.com/pranaydorle514-blip/booksexchange.git C:\xampp\htdocs\BookSwap`.
3. Start **Apache** and **MySQL** in XAMPP.
4. Open `http://localhost/phpmyadmin`, import `database.sql`, and check `config.php`. The default database connection is MySQL host `localhost`, user `root`, empty password.
5. Open `http://localhost/BookSwap/`, choose **Join BookSwap**, and create an account with an email and password.

If upgrading a database created by the earlier WhatsApp-OTP version, run `migration_otp.sql` once in phpMyAdmin before using the password login. It preserves existing phone formatting and the unique phone-number index. Existing email/password accounts retain their passwords.

## Collaborating with GitHub Desktop

The repository is public, so anyone can read and clone it. An invited collaborator with **Write** permission can create branches and push code after accepting the GitHub invitation. In GitHub Desktop, select **File → Clone repository** to get their copy. To update an existing clone, select **Fetch origin**, then **Pull origin** when updates are available. Commit changes locally and use **Push origin** to publish them.

Each developer's local XAMPP database is separate. To share listings and requests between computers, deploy the app and database to a shared host; cloning the repository shares source code only.

## Optional email notifications

Email delivery is disabled until SMTP is configured. Requests are still saved in the website when email is unavailable. To enable notifications, configure `SMTP_HOST`, `SMTP_PORT` (usually `587`), `SMTP_USERNAME`, `SMTP_PASSWORD`, and `SMTP_FROM_EMAIL` in the Apache process environment. Optional settings are `SMTP_FROM_NAME`, `SMTP_ENCRYPTION` (`tls`, `ssl`, or `none`), and `APP_BASE_URL`. Keep credentials outside the repository. For local XAMPP, use Apache `SetEnv` configuration or Windows environment variables and restart Apache.

## Project structure
- `index.php` – home page, catalogue, search, and filters
- `register.php`, `login.php`, `logout.php` – account access
- `add_book.php`, `book.php` – listings and book details
- `request_book.php`, `requests.php` – buy/exchange request workflow
- `my_listings.php` – seller listing management
- `config.php`, `services.php` – database connection and optional SMTP notifications
- `database.sql` – database schema
- `migration_otp.sql` – one-time upgrade for an earlier local database
- `assets/style.css`, `assets/app.js` – frontend styling and navigation

This is a local college project, not a production marketplace. Public deployment requires HTTPS, secure database/provider configuration, backups, moderation, and privacy/legal pages. It does not process payments or verify student identity.
