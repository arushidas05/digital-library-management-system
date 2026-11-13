<?php
/**
 * Librarian: Overdue Loans & Fine Liabilities Report
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Overdue Items Report - OpenShelf';

process_all_overdue_loans();

$overdueList = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, bc.shelf_location, b.title, b.cover_image,
            u.username, u.email, p.full_name, p.student_id, p.phone,
            f.fine_amount, f.status as fine_status
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     JOIN users u ON br.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     LEFT JOIN fines f ON br.id = f.borrowing_id
     WHERE br.status = 'Overdue'
     ORDER BY br.due_date ASC"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-triangle-exclamation text-danger me-2"></i> Overdue Loans & Fine Recovery Report</h3>
        <p class="text-muted text-sm mb-0">Delinquent borrowing records, accrued overdue fines, and borrower contact information</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/reports/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Reports Overview
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card border border-danger shadow-sm">
      <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list-check me-2"></i> All Unreturned Overdue Physical Items (<?= count($overdueList) ?>)</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($overdueList)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-check-circle fs-1 text-success mb-2"></i>
            <h5 class="fw-bold text-dark">No Overdue Books!</h5>
            <p class="mb-0 text-sm">All borrowed books across the university campus are currently returned or on active schedule.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Borrower / Member</th>
                  <th>Student ID</th>
                  <th>Contact Info</th>
                  <th>Book Title</th>
                  <th>Barcode</th>
                  <th>Due Date</th>
                  <th>Days Late</th>
                  <th>Accrued Fine</th>
                  <th class="text-end">Desk Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($overdueList as $ov): ?>
                  <?php
                    $due = new DateTime($ov['due_date']);
                    $today = new DateTime(date('Y-m-d'));
                    $diff = $today->diff($due);
                  ?>
                  <tr>
                    <td class="fw-bold text-dark"><?= clean_output($ov['full_name'] ?: $ov['username']) ?></td>
                    <td><code><?= clean_output($ov['student_id'] ?: 'N/A') ?></code></td>
                    <td>
                      <div><i class="fas fa-phone text-muted me-1"></i> <?= clean_output($ov['phone'] ?: 'N/A') ?></div>
                      <small class="text-muted"><i class="fas fa-envelope text-muted me-1"></i> <?= clean_output($ov['email']) ?></small>
                    </td>
                    <td class="fw-semibold"><?= clean_output($ov['title']) ?></td>
                    <td><code><?= clean_output($ov['barcode']) ?></code></td>
                    <td class="text-danger fw-bold"><?= format_date($ov['due_date']) ?></td>
                    <td>
                      <span class="badge badge-overdue"><?= $diff->days ?> days late</span>
                    </td>
                    <td class="text-danger fw-bold fs-6">
                      <?= format_currency($ov['fine_amount']) ?>
                    </td>
                    <td class="text-end">
                      <a href="<?= BASE_URL ?>/librarian/borrowing/return.php?barcode=<?= urlencode($ov['barcode']) ?>" class="btn btn-sm btn-danger btn-rounded px-3">
                        <i class="fas fa-arrow-rotate-left me-1"></i> Check In
                      </a>
                    </td>
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
