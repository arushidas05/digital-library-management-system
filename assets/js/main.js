/**
 * Core Application JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
  // Initialize tooltips if Bootstrap/MDB is present
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [data-mdb-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    if (window.bootstrap && window.bootstrap.Tooltip) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    }
  });

  // Notification Mark-as-read click handler
  document.querySelectorAll('.notification-item.unread').forEach(item => {
    item.addEventListener('click', function () {
      const notifId = this.getAttribute('data-id');
      if (notifId) {
        fetch(window.APP_BASE_URL + '/student/notifications_read.php?id=' + notifId, {
          method: 'POST'
        }).then(() => {
          this.classList.remove('unread');
          const badge = document.getElementById('notifBadge');
          if (badge) {
            let count = parseInt(badge.innerText, 10) - 1;
            if (count > 0) {
              badge.innerText = count;
            } else {
              badge.style.display = 'none';
            }
          }
        }).catch(err => console.log('Notification mark error:', err));
      }
    });
  });
});
