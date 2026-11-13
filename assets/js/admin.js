/**
 * Admin & Librarian Console Interactions
 */

function generateBarcode(prefix = 'BAR') {
  const randomNum = Math.floor(100000 + Math.random() * 900000);
  const input = document.getElementById('barcodeInput');
  if (input) {
    input.value = prefix + '-' + randomNum;
  }
}

function calculateFinePreview(dueDateStr, fineRate = 5) {
  const previewEl = document.getElementById('finePreviewAmount');
  if (!previewEl || !dueDateStr) return;

  const due = new Date(dueDateStr);
  const today = new Date();
  today.setHours(0,0,0,0);
  due.setHours(0,0,0,0);

  const diffTime = today - due;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays > 0) {
    const total = diffDays * fineRate;
    previewEl.innerHTML = '<span class="text-danger fw-bold">$' + total.toFixed(2) + ' (' + diffDays + ' days overdue)</span>';
  } else {
    previewEl.innerHTML = '<span class="text-success fw-bold">$0.00 (On Time)</span>';
  }
}

function confirmDelete(message = 'Are you sure you want to delete this record? This action cannot be undone.') {
  return confirm(message);
}
