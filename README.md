# Taskly — Database-Driven To-Do List

A PHP + MySQL to-do list web app built for **COMP016 Web Development Lab Activity 5**. Inspired by Todoist's clean, minimal design.

## Features

- Full **CRUD** operations (Create, Read, Update, Delete) with MySQL via PDO
- **Toggle** task status between pending and completed
- Filter tasks by **date** and **label**
- SQL **prepared statements** to prevent SQL Injection
- **XSS protection** with output escaping
- **PRG pattern** (Post-Redirect-Get) to prevent duplicate submissions
- External **CSS** styling and **JavaScript** interactivity
- Responsive design with mobile sidebar

## Prerequisites

- [Laragon](https://laragon.org/download/) (includes Apache, MySQL, and PHP)
- Git

## Setup Instructions

### 1. Clone the Repository

Open a terminal and navigate to your Laragon `www` directory:

```bash
cd C:\laragon\www
git clone https://github.com/Kivyier/Taskly.git
```

### 2. Start Laragon Services

1. Open **Laragon**
2. Click **"Start All"** to start Apache and MySQL

### 3. Import the Database

**Option A — Using HeidiSQL (comes with Laragon):**

1. In Laragon, click **"Database"** to open HeidiSQL
2. Connect with User: `root` and your MySQL password (default is blank)
3. Go to the **Query** tab
4. Open or paste the contents of `task_db.sql` from the project folder
5. Press **F9** to execute
6. Press **F5** to refresh — you should see `task_db` in the left panel

**Option B — Using MySQL CLI (Laragon Terminal):**

1. In Laragon, click **"Terminal"**
2. Run:

```bash
mysql -u root -p < C:\laragon\www\Taskly\task_db.sql
```

3. Enter your MySQL root password when prompted

**Option C — Using MySQL Workbench:**

1. Open MySQL Workbench and connect to `127.0.0.1:3306` with user `root`
2. Go to **File → Open SQL Script** and select `task_db.sql` from the project folder
3. Click the ⚡ lightning bolt button to execute

### 4. Configure the Database Password

If your MySQL root user has a password, update it in `db.php`:

```php
$password = 'your_mysql_password_here';
```

> **Note:** The default in the repo is `$password = ''` (blank), which works with Laragon's default MySQL setup.

### 5. Open the Website

Visit in your browser:

```
http://localhost/Taskly
```

## Project Structure

```
Taskly/
├── db.php          # PDO database connection module
├── index.php       # Main application (PHP + HTML)
├── style.css       # External stylesheet
├── task_db.sql     # Database schema + sample data
├── js/
│   └── script.js   # External JavaScript (delete confirm, edit toggle, etc.)
└── README.md
```

## Troubleshooting

| Problem | Solution |
|---|---|
| **"Access denied for user 'root'@'localhost'"** | Update the `$password` in `db.php` to match your MySQL root password |
| **"Unknown database 'task_db'"** | You haven't imported `task_db.sql` yet — follow Step 3 above |
| **Page is blank or shows raw PHP** | Make sure Apache is running in Laragon |
| **Port 80 already in use** | Stop other apps using port 80 (e.g. Skype, IIS) or change Apache's port in Laragon settings |
