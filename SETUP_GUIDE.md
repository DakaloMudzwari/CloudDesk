# 🚀 CloudDesk | Full-Stack Setup Guide

Follow these steps to get the CloudDesk IT Support Platform running on a new machine.

## 1. Automated Setup (Recommended)
If you are on a fresh Windows machine, you can install everything (PHP, MySQL, Workbench) automatically:
1.  Right-click `auto-install-clouddesk.ps1` and select **Run with PowerShell**.
2.  Follow the prompts. It will download and configure your entire environment.

---

## 2. Prerequisites (Manual Setup)
If you prefer manual installation, ensure these are installed:
*   **PHP 8.4**: (Required for Database connectivity).
*   **MySQL Server 8.4**: (Running on port 3306).
*   **MySQL Workbench**: For database management.

---

## 2. Database Configuration (MySQL Workbench)
1.  Open **MySQL Workbench** and connect to your local instance.
2.  Go to `File` > `Open SQL Script...` and select `database/schema.sql` from the project folder.
3.  Click the **Lightning Bolt** icon to execute the script.
    *   This creates the `clouddesk_db` database and all required tables.
4.  **Important**: Ensure your MySQL root user has **no password** (or update `api/config.php` with your password).

---

## 4. Backend & Server Setup
To run the application, use the automated launcher:

1.  **Launch Server**:
    Right-click `run-server.ps1` and select **Run with PowerShell**.
    *   This script automatically finds your PHP installation and starts the server at `http://localhost:8000`.

2.  **Manual Alternative**:
    If you have PHP in your System PATH, you can run:
    ```powershell
    php -S localhost:8000
    ```

---

## 4. Accessing the Platform
Once the server is running:
1.  Open your browser and go to: **`http://localhost:8000`**
2.  **Primary Admin Account**:
    *   **Email**: `admin@clouddesk.com`
    *   **Password**: `Admin@123`

---

## 📂 Project Structure
*   `/api`: PHP Backend logic and Database configuration.
*   `/assets`: CSS Design System (Glassmorphism) and JS Logic.
*   `/database`: SQL Schema and local data storage.
*   `index.html`: The central entry point (Login).

---

## 🛠️ Troubleshooting
*   **Database Error**: Open `api/config.php` and verify that the `$user` and `$pass` variables match your MySQL Workbench credentials.
*   **Port Conflict**: If port 8000 is busy, change the port in `run-server.ps1` or run `php -S localhost:8080`.
