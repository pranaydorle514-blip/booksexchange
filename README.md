# BookSwap – Second-Hand Book Marketplace

Source project for the [booksexchange GitHub repository](https://github.com/pranaydorle514-blip/booksexchange).

A working PHP + MySQL web application based on the uploaded BookSwap PBL presentation.

## Features
- Student registration and sign-in using one-time WhatsApp codes
- Browse available academic books
- Automatically browse and search Open Library's book catalog
- Browse up to 1,000 catalog records with author-name metadata
- Prefill a sale listing from an Open Library result
- Show a short seller-written buyer preview and edition reference details without exposing full book text
- Search by title, author, subject and description
- Filter by subject, condition and maximum price
- Add book listings with branch, semester, condition, price and exchange option
- View book and seller details
- Send buy or exchange requests
- Email the seller when a buyer sends a request
- Sellers can accept or decline requests
- Manage own listings, mark books sold, relist or delete
- Responsive desktop and mobile layout
- MySQL persistence using PDO prepared statements

## Requirements
- Windows 10/11
- XAMPP (Apache + MySQL)
- A modern browser
- VS Code (optional)

## Setup in XAMPP
1. Install and open XAMPP.
2. Start **Apache** and **MySQL**.
3. Copy the complete `BookSwap` folder into `C:\xampp\htdocs\`.
4. Open `http://localhost/phpmyadmin`.
5. Select **Import**, choose `database.sql` from this project, then click **Import** / **Go**.
   - The SQL creates the `bookswap` database and tables.
   - For an existing installation, run `migration_otp.sql` once in phpMyAdmin instead. It converts 10-digit Indian phone numbers to `+91` format and adds OTP storage and unique phone numbers. Resolve any duplicate phone numbers before running it.
6. Check `config.php`. Default XAMPP settings are host `localhost`, user `root`, empty password. If you set a MySQL password, update `$dbpass`.
7. Open `http://localhost/BookSwap/`.
8. Keep an internet connection available to load the Open Library catalog. Catalog records are book metadata, not confirmed BookSwap listings for sale.

## Configure WhatsApp OTP and email

Real delivery needs provider accounts; the application does not include credentials. Set these environment variables for the Apache process, then restart Apache. Do not commit access tokens, SMTP passwords, or other secrets.

**Meta WhatsApp Cloud API**
- `META_WA_PHONE_NUMBER_ID` – the ID of your WhatsApp Business phone number
- `META_WA_ACCESS_TOKEN` – a server-side access token with permission to send WhatsApp messages
- `META_GRAPH_API_VERSION` – a supported Graph API version, such as `v23.0`
- `META_WA_TEMPLATE_NAME` – optional; defaults to `authentication`
- `META_WA_TEMPLATE_LANGUAGE` – optional; defaults to `en_US`

Create and get approval for an authentication OTP template in WhatsApp Manager. Its language, name, and OTP button must match the configured template. The phone must be able to receive WhatsApp messages; formatting is international (`+` and country code), with 10-digit Indian numbers accepted as `+91`.

**Seller email notifications**
- `SMTP_HOST`, `SMTP_PORT` (usually `587`), `SMTP_USERNAME`, `SMTP_PASSWORD`
- `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME` (optional; defaults to `BookSwap`)
- `SMTP_ENCRYPTION` (`tls`, `ssl`, or `none`; defaults to `tls`)
- `APP_BASE_URL` (optional; defaults to `http://localhost/BookSwap`)

Use your email provider’s SMTP credentials or an app password, not your normal account password where app passwords are available. When SMTP is not configured or delivery fails, the request remains saved and BookSwap reports that the email notification failed.

For local XAMPP on Windows, add the variables in Apache's configuration (for example, `C:\xampp\apache\conf\extra\httpd-vhosts.conf` using `SetEnv`) or configure them in the Windows environment inherited by Apache, then restart Apache. Keep secret values outside the project files.

## First use
1. Click **Join BookSwap** and create a student account.
2. Log in and choose **Sell a book**.
3. Add a book with title, author, subject and condition.
4. Open the Explore page in another browser/incognito session and create a second account to test buyer requests.
5. The seller can open **Requests** and accept or decline.
6. The seller can open **My listings** and mark the book sold when the handover is complete.

## Project structure
- `index.php` – home page, book catalogue, search and filters
- `register.php`, `login.php`, `logout.php` – account access
- `add_book.php`, `book.php` – listing and book details
- `request_book.php`, `requests.php` – buy/exchange request workflow
- `my_listings.php` – seller listing management
- `config.php`, `services.php` – database connection, WhatsApp OTP, and SMTP notifications
- `database.sql` – database schema
- `migration_otp.sql` – one-time upgrade for an existing database
- `assets/style.css`, `assets/app.js` – frontend styling and navigation

## Important real-world notes
This is a locally runnable full-stack college project, not yet a public production marketplace. On `localhost`, only this computer can use the site; cross-device requests need a public web host and a shared production database. Buyer and seller arrange payment and handover themselves. The OTP proves control of a WhatsApp number but does not verify student identity. Before public deployment, use HTTPS, protect provider secrets, configure database backups, and add moderation/reporting and privacy/legal pages. Do not publish real student phone numbers without consent.

## Technology mapping from the presentation
Frontend: HTML, CSS, JavaScript  
Backend: PHP  
Database: MySQL  
Development: VS Code + XAMPP
