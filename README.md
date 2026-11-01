# IFAB Flower Auction Management System

A PHP 8 + MySQL starter implementation for a premium flower-auction portal.

## Requirements
- PHP 8.1+
- MySQL 8+
- XAMPP (Apache + MySQL)
- Browser

## Setup (XAMPP)
1. Extract `IFAB-Flower-Auction` into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/phpmyadmin`, create/import the database by importing `database/ifab.sql`.
4. Edit `config/config.php` if your MySQL username/password differs.
5. Visit `http://localhost/IFAB-Flower-Auction/setup_admin.php` once to set the initial admin password, then **delete `setup_admin.php`** from the project folder.
6. Open `http://localhost/IFAB-Flower-Auction/`.
7. Admin login: `admin@ifab.local` / `ChangeMe@123`
   Change this password immediately after first login.

## Included
- Premium public landing page and flower catalogue
- Secure session-based admin login
- Admin dashboard with live database KPI counts
- Flower CRUD (create, edit, delete, active/inactive)
- Auction listing and creation
- Grower and buyer registration
- MySQL schema for bids, orders, payments, enquiries, events, notifications, audit logs
- CSRF tokens, password hashing, prepared statements, output escaping

## Important
This is a functional academic-project foundation, not a production auction/payment gateway.
Real-time bidding, payment gateway integration, email/SMS, image moderation, and production deployment require additional configuration and testing.
