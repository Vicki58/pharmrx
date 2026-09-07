# 💊 PharmRx - Pharmacy Management System

A secure, role-based PHP & MySQL web application developed for **IBL12307: Web Development Laboratory Studio** at the **Technical University of Kenya (TUK)**.

---

## 🌟 Key Features

- **User Authentication:** Registration, login, logout, and password modification with Bcrypt password hashing (`PASSWORD_DEFAULT`).
- **Role-Based Access Control (RBAC):**
  - **Administrator:** Full CRUD privileges for medicines and categories, audit inspection, and sale cancellations.
  - **Normal Staff / Pharmacist:** View-only medicine catalog and point-of-sale transaction recording.
- **Inventory Management (CRUD):** Add, view, edit, and delete medicines with image upload validation (type and size restrictions) and pricing in Kenyan Shillings (KES).
- **Search & Pagination:** Live search filtering across medicine names, categories, and descriptions with SQL pagination.
- **Sales POS & Concurrency:** Real-time price calculator and atomic stock decrementation using database transactions (`SELECT ... FOR UPDATE`).
- **Forensic Activity Logging:** Automatic system-wide audit logging (`Login`, `Logout`, `Add`, `Edit`, `Delete`) with operator, description, date, and timestamp.
- **Enterprise Web Security:**
  - 100% PDO prepared statements preventing SQL Injection.
  - XSS sanitization (`htmlspecialchars`).
  - Session fixation defense (`session_regenerate_id`).
  - HTTP-only cookie security flags.
- **Automated Verification:** Standalone CLI test suite (`test_runner.php`) with 7/7 automated unit checks passing.

---

## 🏗️ Tech Stack

- **Backend:** PHP 8.3 (PDO Database Layer)
- **Database:** MySQL / MariaDB (InnoDB Storage Engine, 3NF Normalization)
- **Frontend:** Semantic HTML5, Vanilla JavaScript (ES6), Custom Responsive CSS
- **Server:** Apache / XAMPP / Laragon

---

## 📂 Project Structure

```text
├── assets/
│   ├── css/style.css        # Custom responsive dark-theme stylesheet
│   ├── js/validation.js     # Client-side form & file upload validation
│   └── uploads/             # Stored medicine packaging images
├── config/
│   ├── db.php               # PDO database connection & env support
│   ├── auth.php             # Session management & requireAdmin() guards
│   └── logger.php           # Database activity logging helper
├── includes/
│   ├── header.php           # Shared layout header
│   ├── sidebar.php          # Navigation panel & role indicators
│   └── footer.php           # Shared scripts & footer
├── categories.php           # Categories management
├── change_password.php      # User password update form
├── database.sql             # Relational MySQL export (5 tables + seeds in KES)
├── index.php                # Dashboard with real-time KPI metrics
├── login.php                # User login portal
├── logout.php               # Session termination & audit logger
├── logs.php                 # System activity audit trail viewer
├── medicines.php            # Catalog with search & pagination
├── medicine_add.php         # Add medicine with image upload
├── medicine_edit.php        # Edit medicine & replace photo
├── medicine_delete.php      # Delete medicine & unlink photo from disk
├── register.php             # User registration
├── sales.php                # Point-of-sale checkout & sales ledger
├── start_server.bat         # One-click local PHP server launcher
└── test_runner.php          # Automated unit test suite
```

---

## 🚀 Getting Started

### 1. Database Setup
1. Start your local MySQL server (via XAMPP or Laragon).
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI.
3. Create database `pharmacy_db` and import `database.sql`:
   ```sql
   CREATE DATABASE pharmacy_db;
   USE pharmacy_db;
   SOURCE database.sql;
   ```

### 2. Launching the App
Double-click `start_server.bat` or run from terminal:
```bash
php -S localhost:8000
```
Open `http://localhost:8000/login.php` in your browser.

### 3. Demo Credentials
| Role | Username | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` |
| **Normal Staff** | `staff` | `staff123` |

### 4. Running the Automated Test Suite
```bash
php test_runner.php
```

---

## 📄 Academic Deliverables
- **Project Report:** `Final_project.pdf` / `PROJECT_REPORT.html`
- **Presentation Slides:** `Pharmacy_Management_System_Presentation.pptx`
- **Presentation Script:** `PRESENTATION_CHEAT_SHEET.html`
