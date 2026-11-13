# OpenShelf — Online Book Reading & Library Management System

A web-based digital library and online reading platform built with **PHP 8.x**, **MySQL / MariaDB**, **MDBootstrap (Material Design Bootstrap 5)**, **FontAwesome 6**, and **JavaScript**.

Developed in accordance with the **BCA Major PHP Project Plan & System Specification (Version 1.0)**.

---

## 🚀 Key System Features

### 1. Dual Physical & Digital Library Integration
- **Physical Copy Inventory Tracking**: Distinguishes logical book titles from unique physical copies, each having a unique barcode, shelf/rack location, and status (`Available`, `Borrowed`, `Reserved`, `Maintenance`, `Damaged`, `Lost`).
- **Circulation Desk Operations**: Book issue/checkout, return check-in, loan duration enforcement (default 14 days), and maximum concurrent loans limit (default 3 books).
- **Overdue Management & Automated Fines**: Real-time overdue day calculations and fine computation based on configurable daily rates (`fine_per_day`), with full cash/card/UPI receipt settlement and waiver recording.
- **Queue-Based Book Reservations**: When 0 physical copies of a book are available on shelf, members can reserve the title into an automated queue. When a copy is returned, the next eligible reservation is automatically activated (`Ready for Pickup`) with a collection deadline and instant in-app notification!

### 2. In-Browser Digital Reading Experience
- **Distraction-Free Online Reader**: In-browser reading with chapter-by-chapter and page navigation.
- **Reading Themes & Custom Typography**:
  - Themes: *Light*, *Warm Sepia*, *Dark Night*, and *Midnight Slate*.
  - Font Families: *Serif (Merriweather/Georgia)*, *Sans-Serif (Inter/Roboto)*, and *Monospace (Fira Code)*.
  - Font Sizing: *Small*, *Normal*, *Large*, and *Extra-Large*.
  - Reading Margins: *Compact*, *Standard*, and *Wide*.
- **Continuous Reading Progress Saving**: Asynchronous auto-save debounced on scroll and periodically synced every 20 seconds.
- **"Continue Reading" Instant Resume**: Shows last read chapter, percentage, and one-click resume on dashboard and book details.
- **Digital Bookmarks & Annotations**: Members can save bookmarks with page markers and write personal study notes per chapter.

### 3. Student / Member Space
- **Member Dashboard**: Real-time KPI counters (active loans, overdue alerts, holds waiting, reading in progress, wishlist count).
- **Interactive Catalogue**: Search by title, author, or ISBN; multi-facet filtering by category, format (physical vs digital), and live availability.
- **Personal Library**: Tabbed organization of *Currently Reading*, *Completed Books*, *Borrowed Physical Copies*, *Wishlist*, and *Reading History*.
- **Book Reviews & Star Ratings**: 1 to 5 star rating widget, verified member critiques, and community reviews.
- **Reading Analytics & Statistics**: Visual graphs of books completed, pages read, and category distribution.
- **Profile & Password Management**: Manage avatar, contact phone, student ID, and secure password updates.

### 4. Librarian & Admin Consoles
- **Librarian Desk**: Dashboard KPIs, circulation counters, fast barcode scanner lookup, issue book form, return processor, and fines ledger.
- **Operational Reports**: Borrowing trends by month, top popular books ranking, and delinquent overdue loans report with member phone numbers.
- **Master Admin Console**: Full role-based user management (Admin, Librarian, Student), user suspension/reactivation, taxonomy controls (Categories, Authors, Publishers), configurable system settings, and security audit logging.

---

## 🛠️ Technology Stack

| Layer | Technology |
|---|---|
| **Frontend Presentation** | HTML5, CSS3, JavaScript (ES6+), MDBootstrap / Bootstrap 5, FontAwesome 6 |
| **Backend Application** | PHP 8.x (Modular, Object-Oriented, MVC-inspired clean separation) |
| **Data Layer / ORM** | MySQL / MariaDB via MySQLi with 100% Prepared Statements |
| **Authentication & Security**| Session-based Auth, Bcrypt Password Hashing, Role-Based Access Control (RBAC), XSS Sanitization |
| **Development Environment** | XAMPP (Apache HTTP Server + MySQL + phpMyAdmin) |

---

## 📂 Project Structure

```text
online-library-system/
│
├── index.php                 # Public landing page with featured books & stats
├── login.php                 # Authentication login screen with demo autofill buttons
├── register.php              # Student membership registration
├── logout.php                # Session destruction & sign out
├── config.php                # Database connection, dynamic Base URL & global settings
├── database.sql              # Complete schema and seed database
├── test_workflow.php         # Automated end-to-end verification test suite
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Global typography, colors, and layout
│   │   ├── auth.css          # Auth forms & demo login cards
│   │   ├── dashboard.css     # KPI stat cards, banners & sidebars
│   │   ├── books.css         # Catalogue grid, spine covers & star ratings
│   │   ├── reader.css        # Online reader distraction-free styling & themes
│   │   └── admin.css         # Data tables, status badges & admin toolbars
│   ├── js/
│   │   ├── main.js           # Core notification click handlers & tooltips
│   │   ├── auth.js           # Quick demo fill & password visibility toggles
│   │   ├── books.js          # Live catalogue filtering & wishlist AJAX
│   │   ├── reader.js         # Digital reader engine: progress save, themes, bookmarks
│   │   ├── dashboard.js      # Alert dismissals & metric counter helpers
│   │   └── admin.js          # Barcode auto-generator & fine calculations
│   └── vendor/
│       ├── mdb.min.css       # Local vendor Material Design Bootstrap styles
│       └── mdb.umd.min.js    # Local vendor interactive UI components
│
├── includes/
│   ├── header.php            # HTML head, Google fonts & CSS assets
│   ├── navbar.php            # Main navbar with search, notifications bell & avatar
│   ├── sidebar.php           # Role-specific responsive sidebar
│   ├── footer.php            # Common footer & JavaScript inclusions
│   ├── auth_check.php        # Authentication guard for protected routes
│   ├── role_check.php        # Role-based access control guard (RBAC)
│   └── functions.php         # Database wrappers, overdue math, auto-reservation logic
│
├── auth/
│   ├── login_process.php     # Password verification, session startup & routing
│   ├── register_process.php  # User creation & profile initialization
│   ├── forgot_password.php   # Password recovery request & token generation
│   └── reset_password.php    # Password update with token validation
│
├── student/
│   ├── dashboard.php         # Member overview with Continue Reading banner
│   ├── catalogue.php         # Searchable, filterable book catalogue
│   ├── book_details.php      # Book metadata, physical copies shelf availability & reviews
│   ├── my-library.php        # Consolidated reading, completed, borrowed & saved books
│   ├── borrowed-books.php    # Active physical loans, due countdowns & fines
│   ├── reservations.php      # Active queue holds, position numbers & cancellations
│   ├── wishlist.php          # Saved wishlist books
│   ├── wishlist_toggle.php   # AJAX endpoint for wishlist add/remove
│   ├── reading-history.php   # Past digital reading sessions & progress
│   ├── bookmarks.php         # Saved bookmarks across all books
│   ├── notes.php             # Saved reading notes with chapter/page markers
│   ├── reading-statistics.php# Reading analytics & completion charts
│   ├── reviews.php           # Submitted member reviews & ratings
│   ├── profile.php           # Account profile & password change
│   └── notifications_read.php# AJAX endpoint to mark notifications read
│
├── reader/
│   ├── reader.php            # Immersive online book reader interface
│   ├── load_book.php         # AJAX endpoint returning chapter content
│   ├── save_progress.php     # Asynchronous auto-save for reading progress
│   ├── bookmark.php          # Bookmark creation & retrieval API
│   └── note.php              # Reading note creation & retrieval API
│
├── librarian/
│   ├── dashboard.php         # Circulation desk KPIs & quick action toolbar
│   ├── books/
│   │   ├── index.php         # Catalogue master records table
│   │   ├── add.php           # Add new book with authors, categories & copy
│   │   ├── edit.php          # Edit book metadata & classification
│   │   ├── delete.php        # Archive book title
│   │   └── details.php       # Logical book details, physical copies & digital chapters
│   ├── copies/
│   │   ├── index.php         # Physical copy inventory table
│   │   ├── add.php           # Register new physical copy & barcode
│   │   ├── edit.php          # Update copy shelf location & barcode
│   │   └── status.php        # Quick status changer (Available/Maintenance/Damaged)
│   ├── borrowing/
│   │   ├── index.php         # Circulation active loans ledger
│   │   ├── issue.php         # Issue copy to member with due date validation
│   │   ├── return.php        # Return check-in with overdue fine & reservation trigger
│   │   └── history.php       # Complete checkout archive
│   ├── reservations/
│   │   ├── index.php         # Hold queue with priority positions
│   │   ├── approve.php       # Fulfill reservation when member arrives
│   │   └── cancel.php        # Cancel reservation
│   ├── fines/
│   │   ├── index.php         # Overdue fines ledger & settlement tracker
│   │   └── settle.php        # Collect fee, issue receipt, or record waiver
│   └── reports/
│       ├── index.php         # Operational reports index
│       ├── borrowing.php     # Monthly checkout trends & timeliness
│       ├── popular.php       # Top popular titles ranking
│       └── overdue.php       # Overdue loans report with student contacts
│
└── admin/
    ├── dashboard.php         # System administration portal overview
    ├── users/
    │   ├── index.php         # User accounts list & status toggle
    │   ├── add.php           # Create Admin, Librarian, or Student account
    │   ├── edit.php          # Edit user details, role & reset password
    │   └── delete.php        # Delete user account
    ├── categories/
    │   └── index.php         # Manage book categories & genres
    ├── authors/
    │   └── index.php         # Manage literary authors & biographies
    ├── publishers/
    │   └── index.php         # Manage publishing houses & contact information
    ├── settings/
    │   └── index.php         # Fine rates, borrow windows, holding limits & toggles
    └── audit_logs/
        └── index.php         # Security audit trail with IP address tracking
```

---

## 🔑 Default Test Accounts & Credentials

For fast academic demonstration and testing, all accounts share the same default password:

| Role | Username | Email | Password | Full Name / Description |
|---|---|---|---|---|
| **Admin** | `admin` | `admin@library.com` | `password123` | System Administrator (Full access) |
| **Librarian** | `librarian` | `librarian@library.com` | `password123` | Eleanor Vance (Circulation Desk) |
| **Student** | `student` | `student@library.com` | `password123` | Alex Morgan (Member ID: `BCA-2026-042`) |
| **Student** | `emma` | `emma@library.com` | `password123` | Emma Watson (Member ID: `BCA-2026-088`) |

> **Pro Tip**: The login page (`login.php`) contains one-click demo autofill buttons for **Student**, **Librarian**, and **Admin**.

---

## 💻 Local Setup & Installation (XAMPP)

1. **Start Apache & MySQL in XAMPP Control Panel**:
   - Ensure the Apache web server and MySQL / MariaDB service are active.

2. **Database Import**:
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin/`) or MySQL CLI.
   - Create the database `library_db`:
     ```sql
     CREATE DATABASE library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Import the included `database.sql` file into `library_db`:
     ```bash
     mysql -u root library_db < database.sql
     ```

3. **Accessing the Web Application**:
   - If using XAMPP `htdocs`, access:
     ```text
     http://localhost/online-library-system/
     ```
   - Or start PHP's built-in server in the project root:
     ```bash
     php -S localhost:8000
     ```
     and open `http://localhost:8000` in your web browser.

---

## 📋 Suggested Final Demonstration Flow (From Specification Section 13)

Follow this step-by-step test sequence to demonstrate every core module:

1. **Sign In as Student**:
   - Go to `/login.php`, click **Student** quick-fill button, and sign in.
2. **Search Catalogue & View Details**:
   - Navigate to **Catalogue**, search for `"Clean Code"` or filter by **Computer Science & IT**.
   - Open the book details page to observe bibliographic details, physical copies on shelf, and category tags.
3. **Open Digital Book & Online Reader**:
   - Click **"Read Online E-Book"**.
   - Navigate between Chapter 1 and Chapter 2.
   - Change font size, switch reading theme to **Warm Sepia** or **Dark Night**.
4. **Create Bookmark & Personal Note**:
   - Click the **Bookmark** button on the bottom bar to mark the page.
   - Open the **Notes** drawer, type a study note, and click **Save Note**.
5. **Demonstrate Auto-Saved Reading Progress**:
   - Exit the reader and reopen the book (or view student dashboard).
   - Notice the **"Continue Reading (80%)"** banner and button!
6. **Submit Review & Add to Wishlist**:
   - Select 5 stars and post a review on the book details page.
   - Click **"Add to Wishlist"**.
7. **Test Book Reservation**:
   - Open `"1984 (Nineteen Eighty-Four)"`. Notice all physical copies are currently borrowed.
   - Click **"Place Reservation Hold"** and verify you are added to the queue!
8. **Logout & Sign In as Librarian**:
   - Go to `/logout.php` and sign in with `librarian` / `password123`.
9. **Process Return & Fine Calculation**:
   - Open **Circulation Desk** &rarr; **Return Book**.
   - Enter or select overdue barcode `BAR-1005-01`.
   - Observe automatic overdue fine calculation (\$30.00 for 6 days overdue).
   - Settle payment via Cash and complete return.
10. **Verify Automated Reservation Activation (Workflow 6.2)**:
    - Observe the system notification: when `BAR-1005-01` was returned, the pending reservation was **automatically activated** (`Ready for Pickup`) with a collection deadline!
11. **Demonstrate Reports & Analytics**:
    - Open **Library Reports** &rarr; view **Borrowing Trends**, **Most Popular Books**, and **Overdue Report**.
12. **Sign In as Admin**:
    - Sign in with `admin` / `password123`.
    - Open **Manage Users** to suspend/edit user accounts.
    - Open **System Settings** to inspect/adjust the daily overdue fine rate and borrow limits.
    - Open **Audit Logs** to review the recorded activity audit trail.
