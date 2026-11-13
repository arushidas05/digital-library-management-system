<?php
/**
 * Global Footer Include
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}
?>
</main>

<footer class="mt-auto">
  <div class="container py-4">
    <div class="row g-4 align-items-center">
      <div class="col-md-6 text-center text-md-start">
        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
          <i class="fas fa-book-reader text-primary fs-4"></i>
          <span class="fw-bold fs-5 text-dark">OpenShelf</span>
        </div>
        <p class="text-muted text-sm mb-0">Integrated Online Book Reading & Library Management System. Academic BCA Project.</p>
      </div>
      <div class="col-md-6 text-center text-md-end">
        <div class="d-flex justify-content-center justify-content-md-end gap-3 text-sm mb-2">
          <a href="<?= BASE_URL ?>/student/catalogue.php" class="text-muted text-decoration-none hover-primary">Book Catalogue</a>
          <span class="text-muted">•</span>
          <a href="<?= BASE_URL ?>/student/catalogue.php?format=digital" class="text-muted text-decoration-none hover-primary">Digital Reader</a>
          <span class="text-muted">•</span>
          <a href="<?= BASE_URL ?>/login.php" class="text-muted text-decoration-none hover-primary">Portal Login</a>
        </div>
        <div class="text-xs text-muted">
          &copy; <?= date('Y') ?> OpenShelf Library System. Built with PHP, MySQL & MDBootstrap.
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- MDBootstrap JS -->
<script src="<?= BASE_URL ?>/assets/vendor/mdb.umd.min.js"></script>

<!-- Custom App JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
<script src="<?= BASE_URL ?>/assets/js/books.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
