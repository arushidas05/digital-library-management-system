<?php
/**
 * Admin: Security & Activity Audit Logs
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$pageTitle = 'Audit Logs - OpenShelf';

$search = trim($_GET['search'] ?? '');
$actionFilter = trim($_GET['action'] ?? '');

$where = ["1=1"];
$params = [];
$types = "";

if (!empty($actionFilter)) {
    $where[] = "l.action = ?";
    $params[] = $actionFilter;
    $types .= "s";
}

if (!empty($search)) {
    $where[] = "(l.details LIKE ? OR u.username LIKE ? OR l.ip_address LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}

$whereSql = implode(" AND ", $where);

$logs = db_fetch_all(
    "SELECT l.*, u.username, r.name as role_name
     FROM audit_logs l
     LEFT JOIN users u ON l.user_id = u.id
     LEFT JOIN roles r ON u.role_id = r.id
     WHERE {$whereSql}
     ORDER BY l.created_at DESC LIMIT 100",
    $params,
    $types
);

// Fetch distinct actions for filter
$distinctActions = db_fetch_all("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-clipboard-list text-primary me-2"></i> Security & Audit Trail</h3>
        <p class="text-muted text-sm mb-0">Immutable records of authentication events, circulation actions, and configuration changes</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Search & Action Filters -->
    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <form action="<?= BASE_URL ?>/admin/audit_logs/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control" name="search" value="<?= clean_output($search) ?>" placeholder="Search details, username, or IP address...">
          </div>
        </div>
        <div class="col-md-4">
          <select class="form-select form-select-sm" name="action">
            <option value="">All Audit Action Types</option>
            <?php foreach ($distinctActions as $da): ?>
              <option value="<?= $da['action'] ?>" <?= $actionFilter === $da['action'] ? 'selected' : '' ?>>
                <?= clean_output($da['action']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
          <?php if (!empty($search) || !empty($actionFilter)): ?>
            <a href="<?= BASE_URL ?>/admin/audit_logs/index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Logs Table -->
    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <?php if (empty($logs)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-clipboard-list fs-1 text-light mb-2"></i>
            <p class="mb-0">No audit events found matching your filter criteria.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>User / Role</th>
                  <th>Action Type</th>
                  <th>Activity Details</th>
                  <th>Origin IP</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td class="text-nowrap"><?= format_date($log['created_at'], 'M d, Y h:i:s A') ?></td>
                    <td>
                      <?php if ($log['username']): ?>
                        <div class="fw-bold text-dark"><?= clean_output($log['username']) ?></div>
                        <span class="badge bg-light text-muted border text-xs"><?= clean_output($log['role_name'] ?: 'User') ?></span>
                      <?php else: ?>
                        <span class="text-muted text-xs">System / Guest</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge bg-light text-primary border"><?= clean_output($log['action']) ?></span>
                    </td>
                    <td class="text-xs text-dark" style="max-width: 380px; word-break: break-word;">
                      <?= clean_output($log['details']) ?>
                    </td>
                    <td><code><?= clean_output($log['ip_address']) ?></code></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
