<?php
/**
 * Borrowed Books & Fines Page
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'Borrowed Books & Fines - OpenShelf';
$user = current_user();
$userId = $user['id'];

process_all_overdue_loans();

// Active Loans
$activeLoans = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, bc.shelf_location, b.id as book_id, b.title, b.cover_image,
            f.fine_amount, f.status as fine_status
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     LEFT JOIN fines f ON br.id = f.borrowing_id
     WHERE br.user_id = ? AND br.status IN ('Issued', 'Overdue')
     ORDER BY br.due_date ASC",
    [$userId],
    "i"
);

// Past Returns
$pastLoans = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, b.id as book_id, b.title,
            f.fine_amount, f.paid_amount, f.status as fine_status, f.payment_method
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     LEFT JOIN fines f ON br.id = f.borrowing_id
     WHERE br.user_id = ? AND br.status = 'Returned'
     ORDER BY br.return_date DESC",
    [$userId],
    "i"
);

// Total Unpaid Fines
$finesTotalRow = db_fetch_one("SELECT SUM(fine_amount - paid_amount) as total_unpaid FROM fines WHERE user_id = ? AND status = 'Unpaid'", [$userId], "i");
$totalUnpaidFines = (float)($finesTotalRow['total_unpaid'] ?? 0);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-hand-holding-box text-primary me-2"></i> Physical Book Loans & Fines</h3>
        <p class="text-muted text-sm mb-0">View your active borrowings, due dates, overdue items, and settlement records</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-search me-1"></i> Borrow More Books
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Fines Warning Banner if unpaid balance exists -->
    <?php if ($totalUnpaidFines > 0): ?>
      <div class="card bg-danger text-white mb-4 shadow-sm border-0">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="bg-white text-danger rounded-circle p-3 d-inline-flex">
              <i class="fas fa-triangle-exclamation fs-3"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-1">Outstanding Library Overdue Balance: <?= format_currency($totalUnpaidFines) ?></h5>
              <p class="mb-0 text-white-50 text-sm">
                Fine rate: <?= format_currency(get_setting('fine_per_day', 5)) ?> per day. Please return overdue physical books to the library counter to stop further fines and clear your dues.
              </p>
            </div>
          </div>
          <span class="badge bg-white text-danger fs-6 px-3 py-2 fw-bold">Pay at Circulation Desk</span>
        </div>
      </div>
    <?php endif; ?>

    <!-- Active Loans Table -->
    <div class="card mb-4 shadow-sm border">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-book-bookmark text-primary me-2"></i> Currently Issued Books (<?= count($activeLoans) ?>)</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($activeLoans)): ?>
          <div class="text-center py-5 text-muted">
            <i class="far fa-circle-check fs-2 text-success mb-2"></i>
            <p class="mb-0">You have no active loans at the moment. All borrowed books returned!</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Book Title</th>
                  <th>Barcode</th>
                  <th>Issued On</th>
                  <th>Due Date</th>
                  <th>Remaining Time</th>
                  <th>Shelf Location</th>
                  <th>Status & Fine</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($activeLoans as $loan): ?>
                  <?php
                    $due = new DateTime($loan['due_date']);
                    $today = new DateTime(date('Y-m-d'));
                    $isOverdue = ($today > $due);
                    $diff = $today->diff($due);
                    $daysDiff = (int)$diff->days;
                  ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img src="<?= get_book_cover($loan['cover_image'], $loan['title']) ?>" alt="Cover" style="width: 38px; height: 50px; object-fit: cover; border-radius: 4px;">
                        <div>
                          <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $loan['book_id'] ?>" class="text-dark fw-bold text-decoration-none hover-primary">
                            <?= clean_output($loan['title']) ?>
                          </a>
                          <small class="text-muted d-block">Copy #<?= clean_output($loan['copy_number']) ?></small>
                        </div>
                      </div>
                    </td>
                    <td><code><?= clean_output($loan['barcode']) ?></code></td>
                    <td><?= format_date($loan['issue_date']) ?></td>
                    <td class="fw-bold <?= $isOverdue ? 'text-danger' : 'text-dark' ?>">
                      <?= format_date($loan['due_date']) ?>
                    </td>
                    <td>
                      <?php if ($isOverdue): ?>
                        <span class="text-danger fw-bold"><i class="fas fa-circle-exclamation me-1"></i> Overdue by <?= $daysDiff ?> days</span>
                      <?php else: ?>
                        <span class="text-success"><i class="far fa-clock me-1"></i> <?= $daysDiff ?> days left</span>
                      <?php endif; ?>
                    </td>
                    <td><?= clean_output($loan['shelf_location']) ?></td>
                    <td>
                      <?php if ($isOverdue): ?>
                        <span class="badge badge-overdue d-block mb-1">Overdue</span>
                        <small class="text-danger fw-bold">Fine: <?= format_currency($loan['fine_amount'] ?: ($daysDiff * (float)get_setting('fine_per_day', 5))) ?></small>
                      <?php else: ?>
                        <span class="badge badge-borrowed">Active Loan</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Past Loans Archive Table -->
    <div class="card shadow-sm border">
      <div class="card-header bg-white fw-bold">
        <i class="fas fa-history text-secondary me-2"></i> Returned Books & Fine Settlement History
      </div>
      <div class="card-body p-0">
        <?php if (empty($pastLoans)): ?>
          <div class="text-center py-4 text-muted text-sm">
            No historical returns recorded yet.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Book Title</th>
                  <th>Barcode</th>
                  <th>Issued Date</th>
                  <th>Returned On</th>
                  <th>Fine Incurred</th>
                  <th>Payment Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pastLoans as $pl): ?>
                  <tr>
                    <td class="fw-semibold">
                      <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $pl['book_id'] ?>" class="text-dark text-decoration-none hover-primary">
                        <?= clean_output($pl['title']) ?>
                      </a>
                    </td>
                    <td><code><?= clean_output($pl['barcode']) ?></code></td>
                    <td><?= format_date($pl['issue_date']) ?></td>
                    <td><?= format_date($pl['return_date']) ?></td>
                    <td><?= $pl['fine_amount'] > 0 ? format_currency($pl['fine_amount']) : '—' ?></td>
                    <td>
                      <?php if ($pl['fine_amount'] > 0): ?>
                        <?php if ($pl['fine_status'] === 'Paid'): ?>
                          <span class="badge bg-success"><i class="fas fa-check me-1"></i> Paid (<?= clean_output($pl['payment_method']) ?>)</span>
                        <?php elseif ($pl['fine_status'] === 'Waived'): ?>
                          <span class="badge bg-secondary">Waived</span>
                        <?php else: ?>
                          <span class="badge bg-danger">Unpaid</span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge bg-light text-success border">No Fine</span>
                      <?php endif; ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
