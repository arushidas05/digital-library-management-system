/**
 * Dashboard JavaScript Helpers
 */

document.addEventListener('DOMContentLoaded', function () {
  // Auto dismiss flash alerts after 5 seconds if not closed
  const flashAlerts = document.querySelectorAll('.alert-dismissible');
  flashAlerts.forEach(alert => {
    setTimeout(() => {
      if (alert && alert.classList.contains('show')) {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 300);
      }
    }, 6000);
  });
});
