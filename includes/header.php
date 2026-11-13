<?php
/**
 * Global Header Include
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

$pageTitle = $pageTitle ?? 'OpenShelf - Digital & Physical Library System';
$currentUser = current_user();
$unreadNotifsCount = $currentUser ? get_unread_notifications_count($currentUser['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= clean_output($pageTitle) ?></title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=National+Park:wght@200..800&family=Inter:wght@300;400;500;600;700;800&family=Merriweather:ital,wght@0,300;0,400;0,700;1,300&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">

  <!-- Font Awesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- MDBootstrap UI Kit -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/mdb.min.css">

  <!-- Custom Core & Module Stylesheets -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/books.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">

  <!-- Global Application Base URL for JS -->
  <script>
    window.APP_BASE_URL = "<?= BASE_URL ?>";
  </script>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main>
