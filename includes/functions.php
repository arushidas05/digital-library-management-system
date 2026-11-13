<?php
/**
 * Core Helper Functions
 * Online Book Reading & Library Management System
 */

// 1. Sanitization & Output
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function clean_output($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// 2. Database Prepared Query Helpers
function db_query($sql, $params = [], $types = "") {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("DB Prepare Error: " . $conn->error . " | Query: " . $sql);
        return false;
    }

    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat("s", count($params));
            // Adjust int and double if known
            $types = "";
            foreach ($params as $p) {
                if (is_int($p)) {
                    $types .= "i";
                } elseif (is_float($p)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }
        }
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("DB Execute Error: " . $stmt->error . " | Query: " . $sql);
        return false;
    }

    return $stmt;
}

function db_fetch_one($sql, $params = [], $types = "") {
    $stmt = db_query($sql, $params, $types);
    if (!$stmt) return null;
    $result = $stmt->get_result();
    $data = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $data;
}

function db_fetch_all($sql, $params = [], $types = "") {
    $stmt = db_query($sql, $params, $types);
    if (!$stmt) return [];
    $result = $stmt->get_result();
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $stmt->close();
    return $rows;
}

function db_execute($sql, $params = [], $types = "") {
    $stmt = db_query($sql, $params, $types);
    if (!$stmt) return false;
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected >= 0 ? true : false;
}

function db_last_id() {
    global $conn;
    return $conn->insert_id;
}

function db_count($table, $where = "1", $params = [], $types = "") {
    $row = db_fetch_one("SELECT COUNT(*) as cnt FROM `{$table}` WHERE {$where}", $params, $types);
    return $row ? (int)$row['cnt'] : 0;
}

// 3. Flash Messages
function set_flash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function has_flash() {
    return !empty($_SESSION['flash']);
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $messages = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $messages;
    }
    return [];
}

function render_flash() {
    $flashes = get_flash();
    if (empty($flashes)) return;

    foreach ($flashes as $flash) {
        $type = $flash['type'];
        $alertClass = match($type) {
            'success' => 'alert-success',
            'error', 'danger' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
        $iconClass = match($type) {
            'success' => 'fas fa-check-circle',
            'error', 'danger' => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            default => 'fas fa-info-circle'
        };
        echo "<div class='alert {$alertClass} alert-dismissible fade show d-flex align-items-center mb-3 shadow-sm' role='alert'>";
        echo "<i class='{$iconClass} me-2 fs-5'></i>";
        echo "<div>" . clean_output($flash['message']) . "</div>";
        echo "<button type='button' class='btn-close' data-mdb-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
    }
}

// 4. System Settings
function get_setting($key, $default = '') {
    $row = db_fetch_one("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key], "s");
    return $row ? $row['setting_value'] : $default;
}

function update_setting($key, $value, $description = null) {
    $exists = db_fetch_one("SELECT id FROM system_settings WHERE setting_key = ?", [$key], "s");
    if ($exists) {
        if ($description !== null) {
            return db_execute("UPDATE system_settings SET setting_value = ?, description = ? WHERE setting_key = ?", [$value, $description, $key], "sss");
        } else {
            return db_execute("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key], "ss");
        }
    } else {
        return db_execute("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)", [$key, $value, $description ?? ''], "sss");
    }
}

// 5. Audit Logging
function log_audit($action, $details = '') {
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_execute("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)", [$userId, $action, $details, $ip], "isss");
}

// 6. In-App Notifications
function send_notification($userId, $title, $message, $type = 'general', $link = null) {
    return db_execute(
        "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
        [$userId, $title, $message, $type, $link],
        "issss"
    );
}

function get_unread_notifications_count($userId) {
    $res = db_fetch_one("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0", [$userId], "i");
    return $res ? (int)$res['unread'] : 0;
}

function get_recent_notifications($userId, $limit = 5) {
    return db_fetch_all("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?", [$userId, $limit], "ii");
}

// 7. Circulation & Overdue Calculations
function calculate_overdue_fine($dueDate, $returnDate = null) {
    $due = new DateTime($dueDate);
    $return = $returnDate ? new DateTime($returnDate) : new DateTime(date('Y-m-d'));
    
    if ($return <= $due) {
        return ['days' => 0, 'fine' => 0.00];
    }
    
    $diff = $due->diff($return);
    $daysOverdue = (int)$diff->days;
    $finePerDay = (float)get_setting('fine_per_day', 5.00);
    $fine = $daysOverdue * $finePerDay;
    
    return [
        'days' => $daysOverdue,
        'fine' => round($fine, 2)
    ];
}

// Automated cron / routine helper: check all active borrowings and update overdue status & fines
function process_all_overdue_loans() {
    $today = date('Y-m-d');
    $overdueLoans = db_fetch_all(
        "SELECT id, user_id, due_date FROM borrowings WHERE status = 'Issued' AND due_date < ?",
        [$today],
        "s"
    );

    foreach ($overdueLoans as $loan) {
        // Mark as overdue
        db_execute("UPDATE borrowings SET status = 'Overdue' WHERE id = ?", [$loan['id']], "i");
        
        $calc = calculate_overdue_fine($loan['due_date']);
        if ($calc['fine'] > 0) {
            // Check if fine record exists
            $existingFine = db_fetch_one("SELECT id FROM fines WHERE borrowing_id = ?", [$loan['id']], "i");
            if ($existingFine) {
                db_execute(
                    "UPDATE fines SET fine_amount = ?, fine_date = ? WHERE borrowing_id = ? AND status = 'Unpaid'",
                    [$calc['fine'], $today, $loan['id']],
                    "dsi"
                );
            } else {
                db_execute(
                    "INSERT INTO fines (borrowing_id, user_id, fine_amount, status, fine_date, notes) VALUES (?, ?, ?, 'Unpaid', ?, 'Overdue book fine')",
                    [$loan['id'], $loan['user_id'], $calc['fine'], $today],
                    "iids"
                );
                // Send notification to member
                send_notification(
                    $loan['user_id'],
                    'Overdue Book Notice',
                    "A borrowed book is overdue by {$calc['days']} days. Current fine: " . format_currency($calc['fine']),
                    'overdue',
                    'borrowed-books.php'
                );
            }
        }
    }
}

// Core Workflow 6.2: Activate next reservation when a copy is returned
function activate_next_reservation($bookId) {
    // Find next pending reservation in queue for this book
    $nextRes = db_fetch_one(
        "SELECT r.id, r.user_id, r.book_id, u.username, b.title 
         FROM reservations r
         JOIN users u ON r.user_id = u.id
         JOIN books b ON r.book_id = b.id
         WHERE r.book_id = ? AND r.status = 'Pending'
         ORDER BY r.queue_position ASC, r.reservation_date ASC
         LIMIT 1",
        [$bookId],
        "i"
    );

    if ($nextRes) {
        $expiryDays = (int)get_setting('reservation_expiry_days', 3);
        $expiryDate = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));

        // Update reservation to Ready
        db_execute(
            "UPDATE reservations SET status = 'Ready', expiry_date = ?, notified_at = NOW() WHERE id = ?",
            [$expiryDate, $nextRes['id']],
            "si"
        );

        // Notify member
        send_notification(
            $nextRes['user_id'],
            'Book Reserved is Ready for Pickup!',
            "Good news! The copy of '{$nextRes['title']}' you reserved is now available. Please collect it from the library desk before {$expiryDate}.",
            'reservation',
            'reservations.php'
        );

        log_audit('RESERVATION_ACTIVATED', "Activated reservation #{$nextRes['id']} for book {$nextRes['title']} (Member: {$nextRes['username']})");
        return $nextRes;
    }
    return null;
}

// 8. Authentication Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $user = db_fetch_one(
            "SELECT u.id, u.username, u.email, u.role_id, u.status, r.name as role_name,
                    p.full_name, p.student_id, p.phone, p.avatar, p.bio
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN user_profiles p ON u.id = p.user_id
             WHERE u.id = ?",
            [$_SESSION['user_id']],
            "i"
        );
    }
    return $user;
}

function user_role() {
    $u = current_user();
    return $u ? $u['role_name'] : null;
}

function is_admin() {
    return user_role() === 'Admin';
}

function is_librarian() {
    return user_role() === 'Librarian';
}

function is_student() {
    return user_role() === 'Student';
}

// 9. Formatters
function format_currency($amount) {
    $symbol = get_setting('currency_symbol', '$');
    return $symbol . number_format((float)$amount, 2);
}

function format_date($dateStr, $format = 'M d, Y') {
    if (!$dateStr) return '—';
    $timestamp = strtotime($dateStr);
    return date($format, $timestamp);
}

function time_ago($datetime) {
    if (!$datetime) return 'Never';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . ' mins ago';
    if ($diff < 86400) return round($diff / 3600) . ' hrs ago';
    if ($diff < 604800) return round($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

// 10. Dynamic Book Cover & Avatar Renderers
function get_book_cover($filename, $title = 'Book') {
    // If cover image file exists in uploads or assets
    if (!empty($filename) && $filename !== 'default-book.png') {
        $path1 = ROOT_PATH . '/uploads/covers/' . $filename;
        $path2 = ROOT_PATH . '/assets/images/covers/' . $filename;
        if (file_exists($path1)) return BASE_URL . '/uploads/covers/' . $filename;
        if (file_exists($path2)) return BASE_URL . '/assets/images/covers/' . $filename;
    }
    
    // Generate beautiful inline SVG cover based on title seed
    $colors = [
        ['#1e3c72', '#2a5298'],
        ['#134e5e', '#71b280'],
        ['#3a1c71', '#d76d77'],
        ['#4b1248', '#f0c27b'],
        ['#0f2027', '#203a43'],
        ['#2b5876', '#4e4376'],
        ['#373b44', '#4286f4']
    ];
    $idx = abs(crc32($title)) % count($colors);
    $c1 = $colors[$idx][0];
    $c2 = $colors[$idx][1];

    $shortTitle = htmlspecialchars(mb_strimwidth($title, 0, 36, "..."));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="420" viewBox="0 0 300 420">' .
           '<defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">' .
           '<stop offset="0%" stop-color="' . $c1 . '"/><stop offset="100%" stop-color="' . $c2 . '"/>' .
           '</linearGradient></defs>' .
           '<rect width="300" height="420" rx="8" fill="url(#g)"/>' .
           '<rect x="18" y="20" width="264" height="380" rx="4" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="2"/>' .
           '<line x1="28" y1="0" x2="28" y2="420" stroke="rgba(0,0,0,0.25)" stroke-width="5"/>' .
           '<circle cx="150" cy="130" r="42" fill="rgba(255,255,255,0.12)"/>' .
           '<path d="M135 120 L150 110 L165 120 L165 145 L150 135 L135 145 Z" fill="#fff" opacity="0.9"/>' .
           '<text x="150" y="230" fill="#ffffff" font-family="Segoe UI, -apple-system, sans-serif" font-weight="bold" font-size="20" text-anchor="middle">' .
           '<tspan x="150" dy="0">' . substr($shortTitle, 0, 18) . '</tspan>' .
           '<tspan x="150" dy="26">' . substr($shortTitle, 18, 20) . '</tspan>' .
           '</text>' .
           '<text x="150" y="320" fill="rgba(255,255,255,0.7)" font-family="Segoe UI, sans-serif" font-size="12" text-anchor="middle">OPENSHELF LIBRARY</text>' .
           '</svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function get_user_avatar($filename, $name = 'User') {
    if (!empty($filename) && $filename !== 'default-avatar.png') {
        $path1 = ROOT_PATH . '/uploads/avatars/' . $filename;
        $path2 = ROOT_PATH . '/assets/images/avatars/' . $filename;
        if (file_exists($path1)) return BASE_URL . '/uploads/avatars/' . $filename;
        if (file_exists($path2)) return BASE_URL . '/assets/images/avatars/' . $filename;
    }

    $initial = strtoupper(substr(trim($name), 0, 1) ?: 'U');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">' .
           '<circle cx="50" cy="50" r="50" fill="#4f46e5"/>' .
           '<text x="50" y="62" fill="#ffffff" font-family="Segoe UI, sans-serif" font-weight="bold" font-size="40" text-anchor="middle">' . $initial . '</text>' .
           '</svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}
