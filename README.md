# KasiConnect Marketplace

A Consumer-to-Consumer (C2C) e-commerce web application designed for South Africa's informal and township economy. Built as part of the ITECA3-12 module project at Eduvos.

## Tech Stack

- PHP 8.x (vanilla, no framework)
- MySQL 8.x
- HTML5 / CSS3 / vanilla JavaScript
- Web server: Apache (XAMPP for local dev) or Nginx (production)

## Features

- User registration with role selection (buyer, seller, admin)
- Secure authentication using bcrypt password hashing and PHP sessions
- Role-based access control (RBAC) across all protected pages
- Product listings with image uploads and category filtering
- Order placement using MySQL transactions
- Proof-of-payment (PoP) upload and seller-side confirmation workflow
- Buyer order history and seller sales dashboard
- Admin dashboard for managing users, products, orders, and reports
- Reporting system for flagging suspicious users, products, or orders

## Setup

1. Place the project under your web server's document root.
2. Create a MySQL database named kasiconnect.
3. Import sql/schema.sql to create the tables and seed categories.
4. Update database credentials in config/db.php.
5. Ensure public/uploads/products/ and public/uploads/pops/ are writable by the web server user.

## Author

Tafara Wilfred Chipato — Eduvos Midrand, ITECA Group 3.
