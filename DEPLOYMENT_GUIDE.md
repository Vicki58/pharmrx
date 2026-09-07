# 🚀 PharmRx Deployment Guide

This guide provides step-by-step instructions for deploying the **PharmRx Pharmacy Management System** to:
1. **Free Cloud Web Hosting (InfinityFree)** – Get a permanent, free live HTTPS URL (e.g. `https://pharmrx.rf.gd`).
2. **Instant Live Presentation Tunnel (Cloudflare Tunnel / Localtunnel)** – Share a live public link in 60 seconds directly from your laptop.
3. **Local Lab Machine Deployment (XAMPP `htdocs`)** – Deploy on university lab computers.
4. **Cloud Git Platforms (Railway / Render)** – Deploy via GitHub.

---

## 🌐 METHOD 1: Free Cloud Web Hosting (InfinityFree) — *Recommended for Submissions*

InfinityFree is 100% free, requires no credit card, and provides PHP 8 + MySQL + phpMyAdmin (the exact same environment taught in class).

### Step 1: Create a Free Account
1. Go to [InfinityFree.com](https://www.infinityfree.com/) and register a free account.
2. Click **Create Account**.
3. Choose **Free Subdomain** (e.g., Domain: `pharmrx-demo`, Extension: `.rf.gd` or `.infinityfreeapp.com`).
4. Complete the setup.

### Step 2: Create the MySQL Database & Import Data
1. In your InfinityFree dashboard, click **Manage** -> **Control Panel (cPanel)**.
2. Under Databases, click **MySQL Databases**.
3. Create a new database named `pharmacy_db`.
4. Note your database connection details displayed on the page:
   - **MySQL Host Name:** (e.g., `sql123.infinityfree.com`)
   - **MySQL User Name:** (e.g., `if0_12345678`)
   - **MySQL Password:** (Your vPanel account password)
   - **MySQL Database Name:** (e.g., `if0_12345678_pharmacy_db`)
5. Open **phpMyAdmin** from cPanel.
6. Select your database -> Click the **Import** tab at the top.
7. Click **Choose File** -> select your `database.sql` file.
8. Scroll down and click **Import** (or **Go**). All 5 tables will be created!

### Step 3: Upload Application Files
1. In cPanel, click **Online File Manager** (or connect via FileZilla FTP).
2. Double-click to enter the **`htdocs`** folder.
3. Upload all files and folders from `Web_Final project` directly into `htdocs`:
   - `assets/`
   - `config/`
   - `includes/`
   - `index.php`, `login.php`, `medicines.php`, `sales.php`, etc.

### Step 4: Update Database Credentials in `config/db.php`
In the File Manager, open `config/db.php` and update the constants with your InfinityFree details:
```php
define('DB_HOST', 'sql123.infinityfree.com'); // Your InfinityFree MySQL Host
define('DB_USER', 'if0_12345678');            // Your InfinityFree MySQL User
define('DB_PASS', 'YourPasswordHere');        // Your InfinityFree MySQL Password
define('DB_NAME', 'if0_12345678_pharmacy_db');// Your InfinityFree Database Name
define('DB_PORT', '3306');
```
Click **Save**. Your site is now live at `https://yourname.rf.gd`!

---

## ⚡ METHOD 2: Instant Public Tunnel (Localtunnel / Cloudflare) — *Fastest for Live Demos*

If you want Dr. Ngwawe or anyone in the room to open your live system on their phone or laptop without configuring remote databases:

### Option A: Using Localtunnel (Zero install)
1. Make sure your local server is running:
   Double-click `start_server.bat` (or run `php -S localhost:8000`).
2. Open a second terminal window and run:
   ```bash
   npx localtunnel --port 8000
   ```
3. It will give you a public URL like:
   ```text
   your url is: https://famous-rabbit-12.loca.lt
   ```
4. Anyone who opens that link will see your live PharmRx application!

### Option B: Using Cloudflare Tunnel
1. Download `cloudflared` from Cloudflare or run:
   ```bash
   winget install Cloudflare.cloudflared
   ```
2. Start the tunnel:
   ```bash
   cloudflared tunnel --url http://localhost:8000
   ```
3. Copy the generated `.trycloudflare.com` URL.

---

## 💻 METHOD 3: Standard University Lab Deployment (XAMPP `htdocs`)

If presenting or submitting on a university lab computer:

1. Install and open **XAMPP Control Panel**.
2. Click **Start** on both **Apache** and **MySQL**.
3. Copy your entire folder `Web_Final project` into:
   ```text
   C:\xampp\htdocs\pharmrx
   ```
4. Open your browser and go to:
   ```text
   http://localhost/phpmyadmin
   ```
5. Click **New** on the left menu, enter database name `pharmacy_db`, and click **Create**.
6. Select `pharmacy_db` -> Click **Import** -> Select `database.sql` -> Click **Import** (or **Go**).
7. Open the application:
   ```text
   http://localhost/pharmrx/login.php
   ```

---

## ☁️ METHOD 4: Cloud Git Platforms (Railway / Render)

PharmRx is already configured to automatically read environment variables in `config/db.php`:
- `DB_HOST`
- `DB_USER`
- `DB_PASS`
- `DB_NAME`
- `DB_PORT`

To deploy on [Railway.app](https://railway.app):
1. Push your repository to GitHub.
2. In Railway, click **New Project** -> **Provision MySQL**.
3. In the MySQL service, open the **Data** tab -> Import `database.sql`.
4. Add a new service from your **GitHub Repo**.
5. In Variables, link `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, and `DB_PORT` to the MySQL service variables.
6. Railway builds and deploys your application automatically!
