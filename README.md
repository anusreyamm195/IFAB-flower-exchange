# IFAB Flower Auction Management System

A PHP 8 + MySQL flower-auction portal. This repository is connected to
`anusreyamm195/IFAB-flower-exchange` on GitHub and includes a Docker-based Render
deployment configuration.

## Run locally with XAMPP

1. Place the project in `C:\xampp\htdocs\IFAB-Flower-Auction`.
2. Start Apache and MySQL in XAMPP.
3. In phpMyAdmin, create and select a database named `ifab_auction`, then
   import `database/ifab.sql`.
4. Open `http://localhost/IFAB-Flower-Auction/`.
5. To initialize the admin password, run
   `C:\xampp\php\php.exe setup_admin.php` from the project directory and enter a
   new password when prompted. Log in as `admin@ifab.local`.

The admin setup script is CLI-only and is intentionally unavailable over HTTP.

## Deploy to Render

The app requires PHP and MySQL; it cannot be deployed as a static site or with
`npm install`. Render runs the app using the included Dockerfile. Render does
not provide managed MySQL, so create a MySQL database with an external provider
that permits connections from Render. Create the database first and import
`database/ifab.sql` into it. The schema file uses the selected database and
does not try to create a database with a fixed name.

1. Push the deployment files to the GitHub `main` branch.
2. In Render, choose **New > Blueprint**, connect the GitHub repository
   `anusreyamm195/IFAB-flower-exchange`, and deploy its `render.yaml`.
3. When prompted, set the MySQL connection values from your database provider:
   `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`. Set `DB_PORT` if your
   provider uses a port other than `3306`. Keep these values in Render's
   environment settings; do not commit database credentials.
4. After the database is imported and the environment values are saved, open
   the Render service URL.
5. To enable the initial admin account, generate a password hash with PHP and
   use your database provider's SQL console to set it for `admin@ifab.local`.
   The `setup_admin.php` helper is CLI-only and never resets an admin password
   over the public website.

The free Render web service can sleep when idle and uses an ephemeral
filesystem. Uploaded flower images and local PHP sessions may be lost when the
service restarts or redeploys. Use persistent or external storage before
relying on uploaded images in production.

## Included

- Public landing page and flower catalogue
- Session-based admin login and dashboard
- Flower listings, auctions, and grower/buyer registration
- MySQL schema for orders, payments, enquiries, events, notifications, and logs
- CSRF tokens, password hashing, prepared statements, and output escaping

This is a functional project foundation, not a production auction or payment
gateway. Real-time bidding, payment integration, notifications, and production
operations require additional configuration and testing.
