/**
 * Books Catalogue & Interactions JS
 */

document.addEventListener('DOMContentLoaded', function () {
  // Client-side quick filter for catalogue cards if present
  const searchInput = document.getElementById('catalogueSearchInput');
  if (searchInput) {
    searchInput.addEventListener('keyup', function () {
      const term = this.value.toLowerCase().trim();
      const bookCards = document.querySelectorAll('.catalogue-book-col');

      bookCards.forEach(card => {
        const title = card.getAttribute('data-title')?.toLowerCase() || '';
        const author = card.getAttribute('data-author')?.toLowerCase() || '';
        const category = card.getAttribute('data-category')?.toLowerCase() || '';
        const isbn = card.getAttribute('data-isbn')?.toLowerCase() || '';

        if (title.includes(term) || author.includes(term) || category.includes(term) || isbn.includes(term)) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  }

  // Interactive Wishlist Toggle
  document.querySelectorAll('.btn-wishlist-toggle').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const bookId = this.getAttribute('data-book-id');
      const icon = this.querySelector('i');
      
      fetch((window.APP_BASE_URL || '') + '/student/wishlist_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'book_id=' + encodeURIComponent(bookId)
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'added') {
          if (icon) {
            icon.classList.remove('far');
            icon.classList.add('fas', 'text-danger');
          }
          this.setAttribute('title', 'Remove from Wishlist');
          alert('Added to your wishlist!');
        } else if (data.status === 'removed') {
          if (icon) {
            icon.classList.remove('fas', 'text-danger');
            icon.classList.add('far');
          }
          this.setAttribute('title', 'Add to Wishlist');
          alert('Removed from your wishlist.');
        } else if (data.error) {
          alert(data.error);
        }
      })
      .catch(err => console.error(err));
    });
  });
});
