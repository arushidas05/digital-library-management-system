-- Online Book Reading & Library Management System
-- Database Schema & Comprehensive Sample Data
-- Stack: PHP 8.x + MariaDB / MySQL

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `wishlists`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `notes`;
DROP TABLE IF EXISTS `bookmarks`;
DROP TABLE IF EXISTS `reading_progress`;
DROP TABLE IF EXISTS `fines`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `borrowings`;
DROP TABLE IF EXISTS `digital_book_chapters`;
DROP TABLE IF EXISTS `digital_books`;
DROP TABLE IF EXISTS `book_copies`;
DROP TABLE IF EXISTS `book_categories`;
DROP TABLE IF EXISTS `book_authors`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `authors`;
DROP TABLE IF EXISTS `publishers`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Roles
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Admin', 'Full administrative control over all system features, configurations, and users'),
(2, 'Librarian', 'Manages catalogue, physical copies, circulation, returns, fines, and reservations'),
(3, 'Student', 'Reads digital books, borrows physical books, reserves, tracks progress, writes reviews');

-- 2. Users
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT NOT NULL DEFAULT 3,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `reset_token` VARCHAR(100) NULL,
  `reset_expires` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Profiles
CREATE TABLE `user_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `full_name` VARCHAR(120) NOT NULL,
  `student_id` VARCHAR(50) NULL,
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `avatar` VARCHAR(255) DEFAULT 'default-avatar.png',
  `bio` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. System Settings
CREATE TABLE `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(80) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `description` VARCHAR(255) NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('fine_per_day', '5.00', 'Fine amount charged per day for overdue physical books'),
('max_borrow_days', '14', 'Standard borrowing period in days'),
('max_books_per_member', '3', 'Maximum concurrent active physical borrowings per student'),
('reservation_expiry_days', '3', 'Number of days a reserved book is held once ready for collection'),
('enable_online_reading', '1', 'Toggle digital reader access across the platform'),
('library_name', 'OpenShelf Digital & Physical Library', 'Library platform title'),
('currency_symbol', '$', 'Currency symbol used for fines');

-- 5. Publishers
CREATE TABLE `publishers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL UNIQUE,
  `address` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `email` VARCHAR(100) NULL,
  `website` VARCHAR(150) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Authors
CREATE TABLE `authors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `biography` TEXT NULL,
  `photo` VARCHAR(255) DEFAULT 'default-author.png',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Categories
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `icon` VARCHAR(50) DEFAULT 'fas fa-book',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Books (Logical Catalogue)
CREATE TABLE `books` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `isbn` VARCHAR(30) NOT NULL UNIQUE,
  `publisher_id` INT NULL,
  `publication_year` INT NULL,
  `edition` VARCHAR(50) NULL,
  `language` VARCHAR(50) DEFAULT 'English',
  `total_pages` INT DEFAULT 0,
  `description` TEXT NULL,
  `cover_image` VARCHAR(255) DEFAULT 'default-book.png',
  `is_physical` TINYINT(1) DEFAULT 1,
  `is_digital` TINYINT(1) DEFAULT 1,
  `status` ENUM('active', 'archived') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Book Authors Mapping
CREATE TABLE `book_authors` (
  `book_id` INT NOT NULL,
  `author_id` INT NOT NULL,
  PRIMARY KEY (`book_id`, `author_id`),
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Book Categories Mapping
CREATE TABLE `book_categories` (
  `book_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  PRIMARY KEY (`book_id`, `category_id`),
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Physical Book Copies
CREATE TABLE `book_copies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `book_id` INT NOT NULL,
  `copy_number` VARCHAR(50) NOT NULL UNIQUE,
  `barcode` VARCHAR(50) NOT NULL UNIQUE,
  `shelf_location` VARCHAR(100) NOT NULL,
  `status` ENUM('Available', 'Borrowed', 'Reserved', 'Maintenance', 'Damaged', 'Lost') DEFAULT 'Available',
  `condition_notes` VARCHAR(255) NULL,
  `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Digital Books
CREATE TABLE `digital_books` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `book_id` INT NOT NULL UNIQUE,
  `file_path` VARCHAR(255) NULL,
  `file_format` VARCHAR(20) DEFAULT 'html/text',
  `file_size_kb` INT DEFAULT 0,
  `total_chapters` INT DEFAULT 1,
  `sample_content` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Digital Book Chapters
CREATE TABLE `digital_book_chapters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `digital_book_id` INT NOT NULL,
  `chapter_number` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`digital_book_id`) REFERENCES `digital_books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Borrowings (Physical Circulation)
CREATE TABLE `borrowings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_copy_id` INT NOT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE NULL,
  `status` ENUM('Issued', 'Returned', 'Overdue', 'Lost') DEFAULT 'Issued',
  `issued_by` INT NULL,
  `received_by` INT NULL,
  `remarks` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_copy_id`) REFERENCES `book_copies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Reservations
CREATE TABLE `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `reservation_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` DATETIME NULL,
  `queue_position` INT DEFAULT 1,
  `status` ENUM('Pending', 'Ready', 'Completed', 'Cancelled', 'Expired') DEFAULT 'Pending',
  `notified_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Fines
CREATE TABLE `fines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `borrowing_id` INT NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Unpaid', 'Paid', 'Waived') DEFAULT 'Unpaid',
  `fine_date` DATE NOT NULL,
  `paid_date` DATE NULL,
  `payment_method` VARCHAR(50) NULL,
  `receipt_no` VARCHAR(60) NULL,
  `notes` TEXT NULL,
  FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Reading Progress
CREATE TABLE `reading_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `current_chapter` INT DEFAULT 1,
  `current_page` INT DEFAULT 1,
  `progress_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `last_read_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `user_book_progress` (`user_id`, `book_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Bookmarks
CREATE TABLE `bookmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `chapter_number` INT DEFAULT 1,
  `page_number` INT DEFAULT 1,
  `title` VARCHAR(150) NOT NULL,
  `note` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Notes
CREATE TABLE `notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `chapter_number` INT DEFAULT 1,
  `page_number` INT DEFAULT 1,
  `content` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Reviews & Ratings
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `review_text` TEXT NOT NULL,
  `status` ENUM('Approved', 'Pending', 'Hidden') DEFAULT 'Approved',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Wishlists
CREATE TABLE `wishlists` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_book_wishlist` (`user_id`, `book_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Notifications
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('reservation', 'due_date', 'overdue', 'fine', 'general') DEFAULT 'general',
  `is_read` TINYINT(1) DEFAULT 0,
  `link` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Audit Logs
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(50) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================================
-- SEED DATA
-- Default password for all users is: password123
-- =========================================================================

-- Seed Users & Profiles
INSERT INTO `users` (`id`, `role_id`, `username`, `email`, `password`, `status`) VALUES
(1, 1, 'admin', 'admin@library.com', '$2y$10$aWpDUBo8xnNNSQWoWuVKZOvyTBa0ci81Fdrm9lbzSeLeiNXiy8CJK', 'active'),
(2, 2, 'librarian', 'librarian@library.com', '$2y$10$aWpDUBo8xnNNSQWoWuVKZOvyTBa0ci81Fdrm9lbzSeLeiNXiy8CJK', 'active'),
(3, 3, 'student', 'student@library.com', '$2y$10$aWpDUBo8xnNNSQWoWuVKZOvyTBa0ci81Fdrm9lbzSeLeiNXiy8CJK', 'active'),
(4, 3, 'emma', 'emma@library.com', '$2y$10$aWpDUBo8xnNNSQWoWuVKZOvyTBa0ci81Fdrm9lbzSeLeiNXiy8CJK', 'active');

INSERT INTO `user_profiles` (`user_id`, `full_name`, `student_id`, `phone`, `address`, `avatar`, `bio`) VALUES
(1, 'System Administrator', 'ADMIN-01', '+1 555-0100', 'Library Headquarters, Suite 400', 'default-avatar.png', 'Chief system administrator overseeing security and catalog configuration.'),
(2, 'Eleanor Vance (Librarian)', 'LIB-002', '+1 555-0102', 'Library Circulation Desk, 1st Floor', 'default-avatar.png', 'Head of Circulation and Physical Asset Management.'),
(3, 'Alex Morgan', 'BCA-2026-042', '+1 555-0199', 'Block B, Campus Hall Residence', 'default-avatar.png', 'Final year BCA student interested in software architecture and classic literature.'),
(4, 'Emma Watson', 'BCA-2026-088', '+1 555-0211', 'Block D, Campus Residence', 'default-avatar.png', 'Student member passionate about 19th century fiction and data structures.');

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `description`, `icon`) VALUES
(1, 'Computer Science & IT', 'Software engineering, algorithms, programming languages, and web technologies.', 'fas fa-laptop-code'),
(2, 'Classic Literature', 'Timeless masterpieces of fiction, historical drama, and prose.', 'fas fa-book-open'),
(3, 'Science Fiction & Dystopia', 'Speculative futures, dystopian societies, and technological wonders.', 'fas fa-rocket'),
(4, 'Mystery & Detective', 'Crime thrillers, deduction puzzles, and investigative adventures.', 'fas fa-search'),
(5, 'Romance & Drama', 'Period romance, emotional journeys, and social commentaries.', 'fas fa-heart'),
(6, 'Philosophy & Psychology', 'Human behavior, cognitive sciences, ethics, and philosophical theories.', 'fas fa-brain');

-- Seed Authors
INSERT INTO `authors` (`id`, `name`, `biography`, `photo`) VALUES
(1, 'Robert C. Martin', 'Known colloquially as Uncle Bob, he is an American software engineer and author of foundational clean architecture books.', 'default-author.png'),
(2, 'F. Scott Fitzgerald', 'American novelist whose works illustrate the Jazz Age and critique of the American Dream.', 'default-author.png'),
(3, 'Jane Austen', 'English novelist known primarily for her six major novels which interpret, critique, and comment upon the British gentry.', 'default-author.png'),
(4, 'Arthur Conan Doyle', 'British writer who created the renowned character Sherlock Holmes.', 'default-author.png'),
(5, 'George Orwell', 'English novelist, essayist, and critic famous for Animal Farm and Nineteen Eighty-Four.', 'default-author.png'),
(6, 'Erich Gamma & Gang of Four', 'Pioneering software engineers who catalogued 23 classic design patterns in modern OOP.', 'default-author.png');

-- Seed Publishers
INSERT INTO `publishers` (`id`, `name`, `address`, `phone`, `email`, `website`) VALUES
(1, 'Prentice Hall / Pearson', 'Upper Saddle River, New Jersey', '+1 800-382-3419', 'info@pearson.com', 'https://www.pearson.com'),
(2, 'Charles Scribner\'s Sons', 'New York City, USA', '+1 212-555-0144', 'contact@scribners.com', 'https://www.simonandschuster.com'),
(3, 'T. Egerton, Whitehall', 'London, United Kingdom', '+44 20 7946 0919', 'editorial@egerton.co.uk', 'https://britishlibrary.org'),
(4, 'George Newnes / Strand Magazine', 'Southampton Street, London', '+44 20 7946 0888', 'archives@strandmag.co.uk', 'https://strandmag.com'),
(5, 'Secker & Warburg', 'London, United Kingdom', '+44 20 7946 0777', 'info@secker.co.uk', 'https://penguin.co.uk'),
(6, 'Addison-Wesley Professional', 'Boston, Massachusetts', '+1 617-555-0123', 'cs@awprofessional.com', 'https://informit.com');

-- Seed Books
INSERT INTO `books` (`id`, `title`, `isbn`, `publisher_id`, `publication_year`, `edition`, `language`, `total_pages`, `description`, `cover_image`, `is_physical`, `is_digital`, `status`) VALUES
(1, 'Clean Code: A Handbook of Agile Software Craftsmanship', '978-0132350884', 1, 2008, '1st Edition', 'English', 464, 'Even bad code can function. But if code is not clean, it can bring a development organization to its knees. Uncle Bob Martin presents a revolutionary paradigm with Clean Code.', 'clean-code.jpg', 1, 1, 'active'),
(2, 'The Great Gatsby', '978-0743273565', 2, 1925, 'Original Edition', 'English', 180, 'The story of the fabulously wealthy Jay Gatsby and his new love for the beautiful Daisy Buchanan, of lavish parties on Long Island at a time when The New York Times noted "gin was the national drink and sex the national obsession".', 'great-gatsby.jpg', 1, 1, 'active'),
(3, 'Pride and Prejudice', '978-0141439518', 3, 1813, 'Definitive Edition', 'English', 279, 'A romantic novel of manners written by Jane Austen. The novel follows the character development of Elizabeth Bennet, the dynamic protagonist of the book who learns about the repercussions of hasty judgments.', 'pride-prejudice.jpg', 1, 1, 'active'),
(4, 'The Adventures of Sherlock Holmes', '978-0140437713', 4, 1892, 'Illustrated Edition', 'English', 307, 'A collection of twelve short stories by Arthur Conan Doyle, first published on 14 October 1892, featuring the consulting detective Sherlock Holmes and his companion Dr. John Watson.', 'sherlock-holmes.jpg', 1, 1, 'active'),
(5, '1984 (Nineteen Eighty-Four)', '978-0451524935', 5, 1949, 'Centennial Edition', 'English', 328, 'Winston Smith toes the Party line, rewriting history to satisfy the Ministry of Truth. With each lie he writes, Winston grows to hate the Party that seeks power for its own sake and persecutes those who dare to commit thoughtcrimes.', '1984.jpg', 1, 0, 'active'),
(6, 'Design Patterns: Elements of Reusable Object-Oriented Software', '978-0201633610', 6, 1994, '1st Edition', 'English', 395, 'Capturing a wealth of experience about the design of object-oriented software, four top-notch designers present a catalog of simple and succinct solutions to commonly occurring design problems.', 'design-patterns.jpg', 1, 1, 'active');

-- Book Authors Mapping
INSERT INTO `book_authors` (`book_id`, `author_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6);

-- Book Categories Mapping
INSERT INTO `book_categories` (`book_id`, `category_id`) VALUES
(1, 1),
(2, 2),
(3, 2),
(3, 5),
(4, 4),
(4, 2),
(5, 3),
(6, 1);

-- Seed Physical Book Copies
-- Note: Book 1 has 3 copies (2 available, 1 borrowed)
-- Book 2 has 2 copies (2 available)
-- Book 3 has 2 copies (1 available, 1 borrowed)
-- Book 4 has 2 copies (2 available)
-- Book 5 has 2 copies (BOTH BORROWED -> 0 available, ready for reservation demo!)
-- Book 6 has 1 copy (1 available)
INSERT INTO `book_copies` (`id`, `book_id`, `copy_number`, `barcode`, `shelf_location`, `status`, `condition_notes`) VALUES
(1, 1, 'CP-CC-01', 'BAR-1001-01', 'Shelf CS-01, Floor 2', 'Available', 'Pristine condition'),
(2, 1, 'CP-CC-02', 'BAR-1001-02', 'Shelf CS-01, Floor 2', 'Available', 'Good condition, minor cover wear'),
(3, 1, 'CP-CC-03', 'BAR-1001-03', 'Shelf CS-01, Floor 2', 'Borrowed', 'Issued to Alex Morgan'),
(4, 2, 'CP-GG-01', 'BAR-1002-01', 'Shelf FIC-04, Floor 1', 'Available', 'Hardcover, excellent'),
(5, 2, 'CP-GG-02', 'BAR-1002-02', 'Shelf FIC-04, Floor 1', 'Available', 'Paperback, good'),
(6, 3, 'CP-PP-01', 'BAR-1003-01', 'Shelf LIT-08, Floor 1', 'Available', 'Classics section, pristine'),
(7, 3, 'CP-PP-02', 'BAR-1003-02', 'Shelf LIT-08, Floor 1', 'Borrowed', 'Issued to Emma Watson'),
(8, 4, 'CP-SH-01', 'BAR-1004-01', 'Shelf MYS-02, Floor 1', 'Available', 'Illustrated, collector copy'),
(9, 4, 'CP-SH-02', 'BAR-1004-02', 'Shelf MYS-02, Floor 1', 'Available', 'Standard reading copy'),
(10, 5, 'CP-84-01', 'BAR-1005-01', 'Shelf DYS-01, Floor 2', 'Borrowed', 'Issued to Alex Morgan (Overdue for demo)'),
(11, 5, 'CP-84-02', 'BAR-1005-02', 'Shelf DYS-01, Floor 2', 'Borrowed', 'Issued to external faculty borrower'),
(12, 6, 'CP-DP-01', 'BAR-1006-01', 'Shelf CS-03, Floor 2', 'Available', 'Hardcover, reference copy');

-- Seed Digital Books
INSERT INTO `digital_books` (`id`, `book_id`, `file_path`, `file_format`, `file_size_kb`, `total_chapters`, `sample_content`) VALUES
(1, 1, 'uploads/books/clean-code.txt', 'html/text', 520, 3, 'Clean Code Principles and Guidelines.'),
(2, 2, 'uploads/books/great-gatsby.txt', 'html/text', 380, 3, 'In my younger and more vulnerable years my father gave me some advice...'),
(3, 3, 'uploads/books/pride-and-prejudice.txt', 'html/text', 650, 4, 'It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife.'),
(4, 4, 'uploads/books/sherlock-holmes.txt', 'html/text', 710, 3, 'To Sherlock Holmes she is always the woman. I have seldom heard him mention her under any other name.');

-- Seed Digital Chapters for Rich Online Reading Demo
-- Pride and Prejudice (Book 3, Digital Book 3)
INSERT INTO `digital_book_chapters` (`digital_book_id`, `chapter_number`, `title`, `content`) VALUES
(3, 1, 'Chapter 1: The Arrival at Netherfield', 'It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife.\n\nHowever little known the feelings or views of such a man may be on his first entering a neighbourhood, this truth is so well fixed in the minds of the surrounding families, that he is considered the rightful property of some one or other of their daughters.\n\n"My dear Mr. Bennet," said his lady to him one day, "have you heard that Netherfield Park is let at last?"\n\nMr. Bennet replied that he had not.\n\n"But it is," returned she; "for Mrs. Long has just been here, and she told me all about it."\n\nMr. Bennet made no answer.\n\n"Do you not want to know who has taken it?" cried his wife impatiently.\n\n"You want to tell me, and I have no objection to hearing it."\n\nThis was invitation enough.\n\n"Why, my dear, you must know, Mrs. Long says that Netherfield is taken by a young man of large fortune from the north of England; that he came down on Monday in a chaise and four to see the place, and was so much delighted with it, that he agreed with Mr. Morris immediately; that he is to take possession before Michaelmas, and some of his servants are to be in the house by the end of next week."\n\n"What is his name?"\n\n"Bingley."\n\n"Is he married or single?"\n\n"Oh! Single, my dear, to be sure! A single man of large fortune; four or five thousand a year. What a fine thing for our girls!"\n\n"How so? How can it affect them?"\n\n"My dear Mr. Bennet," replied his wife, "how can you be so tiresome! You must know that I am thinking of his marrying one of them."\n\n"Is that his design in settling here?"\n\n"Design! Nonsense, how can you talk so! But it is very likely that he may fall in love with one of them, and therefore you must visit him as soon as he comes."'),

(3, 2, 'Chapter 2: An Unexpected Visit', 'Mr. Bennet was among the earliest of those who waited on Mr. Bingley. He had always intended to visit him, though to the last always assuring his wife that he should not go; and till the evening after the visit was paid she had no knowledge of it.\n\nIt was then disclosed in the following manner. Observing his second daughter employed in trimming a hat, he suddenly addressed her with:\n\n"I hope Mr. Bingley will like it, Lizzy."\n\n"We are not in a way to know what Mr. Bingley likes," said her mother resentfully, "since we are not to visit."\n\n"But you forget, mamma," said Elizabeth, "that we shall meet him at the assemblies, and that Mrs. Long has promised to introduce him."\n\n"I do not believe Mrs. Long will do any such thing. She has two nieces of her own. She is a selfish, hypocritical woman, and I have no opinion of her."\n\n"No more have I," said Mr. Bennet; "and I am glad to find that you do not depend on her serving you."\n\nMrs. Bennet deigned not to make any reply, but, unable to contain herself, began scolding one of her daughters.\n\n"Don\'t keep coughing so, Kitty, for heaven\'s sake! Have a little compassion on my nerves. You tear them to pieces."\n\n"Kitty has no discretion in her coughs," said her father; "she times them ill."\n\n"I do not cough for my own amusement," replied Kitty fretfully. "When is your next ball to be, Lizzy?"'),

(3, 3, 'Chapter 3: The Assembly Room and Mr. Darcy', 'Not all that Mrs. Bennet, however, with the assistance of her five daughters, could ask on the subject, was sufficient to draw from her husband any satisfactory description of Mr. Bingley. They attacked him in various ways—with barefaced questions, ingenious suppositions, and distant surmises; but he eluded the skill of them all, and they were at last obliged to accept the second-hand intelligence of their neighbour, Lady Lucas.\n\nHer report was highly favourable. Sir William had been delighted with him. He was quite young, wonderfully handsome, extremely agreeable, and, to crown the whole, he meant to be at the next assembly with a large party.\n\nNothing could be more delightful! To be fond of dancing was a certain step towards falling in love; and very lively hopes of Mr. Bingley\'s heart were entertained.\n\nMr. Bingley was good-looking and gentlemanlike; he had a pleasant countenance, and easy, unaffected manners. His sisters were fine women, with an air of decided fashion. His brother-in-law, Mr. Hurst, merely looked the gentleman; but his friend Mr. Darcy soon drew the attention of the room by his fine, tall person, handsome features, noble mien, and the report which was in general circulation within five minutes after his entrance, of his having ten thousand a year.\n\nThe gentlemen pronounced him to be a fine figure of a man, the ladies declared he was much handsomer than Mr. Bingley, and he was looked at with great admiration for about half the evening, till his manners gave a disgust which turned the tide of his popularity; for he was discovered to be proud; to be above his company, and above being pleased; and not all his large estate in Derbyshire could then save him from having a most forbidding, disagreeable countenance, and being unworthy to be compared with his friend.');

-- The Great Gatsby (Book 2, Digital Book 2)
INSERT INTO `digital_book_chapters` (`digital_book_id`, `chapter_number`, `title`, `content`) VALUES
(2, 1, 'Chapter 1: Advice and the Green Light', 'In my younger and more vulnerable years my father gave me some advice that I’ve been turning over in my mind ever since.\n\n"Whenever you feel like criticizing anyone," he told me, "just remember that all the people in this world haven’t had the advantages that you’ve had."\n\nHe didn’t say any more, but we’ve always been unusually communicative in a reserved way, and I understood that he meant a great deal more than that. In consequence, I’m inclined to reserve all judgements, a habit that has opened up many curious natures to me and also made me the victim of not a few veteran bores.\n\nWhen I came back from the East last autumn I felt that I wanted the world to be in uniform and at a sort of moral attention forever; I wanted no more riotous excursions with privileged glimpses into the human heart. Only Gatsby, the man who gives his name to this book, was exempt from my reaction—Gatsby, who represented everything for which I have an unaffected scorn.\n\nIf personality is an unbroken series of successful gestures, then there was something gorgeous about him, some heightened sensitivity to the promises of life, as if he were related to one of those intricate machines that register earthquakes ten thousand miles away.'),

(2, 2, 'Chapter 2: The Valley of Ashes', 'About half way between West Egg and New York the motor road hastily joins the railroad and runs beside it for a quarter of a mile, so as to shrink away from a certain desolate area of land. This is a valley of ashes—a fantastic farm where ashes grow like wheat into ridges and hills and grotesque gardens; where ashes take the forms of houses and chimneys and rising smoke and, finally, with a transcendent effort, of men who move dimly and already crumbling through the powdery air.\n\nOccasionally a line of gray cars crawls along an invisible track, gives out a ghastly creak, and comes to rest, and immediately the ash-gray men swarm up with leaden spades and stir up an impenetrable cloud, which screens their obscure operations from your sight.\n\nBut above the gray land and the spasms of bleak dust which drift endlessly over it, you perceive, after a moment, the eyes of Doctor T. J. Eckleburg. The eyes of Doctor T. J. Eckleburg are blue and gigantic—their irises are one yard high. They look out of no face, but, instead, from a pair of enormous yellow spectacles which pass over a non-existent nose.');

-- Clean Code (Book 1, Digital Book 1)
INSERT INTO `digital_book_chapters` (`digital_book_id`, `chapter_number`, `title`, `content`) VALUES
(1, 1, 'Chapter 1: Clean Code and Craftsmanship', 'There are two things you need for good software: craftsmanship and discipline. Craftsmanship is acquired through deliberate practice and patience.\n\nYou are reading this book for two reasons: first, you are a programmer; second, you want to be a better programmer. Good. We need better programmers.\n\nWhat is Clean Code?\nBjarne Stroustrup, inventor of C++:\n"I like my code to be elegant and efficient. The logic should be straightforward to make it hard for bugs to hide, the dependencies minimal to ease maintenance, error handling complete according to an articulated strategy, and performance close to optimal so as not to tempt people to make code messy with unprincipled optimizations. Clean code does one thing well."\n\nGrady Booch, author of Object-Oriented Analysis and Design:\n"Clean code is simple and direct. Clean code never obscures the designer\'s intent but rather is full of crisp abstractions and straightforward lines of control."\n\nThe Boy Scout Rule:\n"Leave the campground cleaner than you found it." If we all checked in our code a little cleaner than when we checked it out, the code simply could not rot. The cleanup doesn\'t have to be something big. Change one variable name for the better, break up one function that\'s a little too large, eliminate one little bit of duplication, clean up one composite IF statement.'),

(1, 2, 'Chapter 2: Meaningful Names', 'Names are everywhere in software. We name our variables, our functions, our arguments, classes, and packages. We name our source files and the directories that contain them. We name jar files and war files and ear files. We name and name and name.\n\nBecause we do so much of it, we\'d better do it well. Following are some simple rules for creating good names:\n\n1. Use Intention-Revealing Names:\nThe name of a variable, function, or class, should answer all the big questions. It should tell you why it exists, what it does, and how it is used. If a name requires a comment, then the name does not reveal its intent.\n\n2. Avoid Disinformation:\nProgrammers must avoid leaving false clues that obscure the meaning of code. We should avoid words whose entrenched meanings vary from our intended meaning. For example, do not refer to a grouping of accounts as an accountList unless it\'s actually a List. An AccountGroup or bunchOfAccounts or just Accounts would be better.\n\n3. Make Meaningful Distinctions:\nNumber-series naming (a1, a2, .. aN) is the opposite of intentional naming. Such names are not disinformative—they are noninformative; they provide no clue to the author\'s intention.');

-- Sherlock Holmes (Book 4, Digital Book 4)
INSERT INTO `digital_book_chapters` (`digital_book_id`, `chapter_number`, `title`, `content`) VALUES
(4, 1, 'Chapter 1: A Scandal in Bohemia', 'To Sherlock Holmes she is always THE woman. I have seldom heard him mention her under any other name. In his eyes she eclipses and predominates the whole of her sex. It was not that he felt any emotion akin to love for Irene Adler. All emotions, and that one particularly, were abhorrent to his cold, precise but admirably balanced mind.\n\nHe was, I take it, the most perfect reasoning and observing machine that the world has seen, but as a lover he would have placed himself in a false position. He never spoke of the softer passions, save with a gibe and a sneer. They were admirable things for the observer—excellent for drawing the veil from men’s motives and actions. But for the trained reasoner to admit such intrusions into his own delicate and finely adjusted temperament was to introduce a distracting factor which might throw a doubt upon all his mental results.\n\nGrit in a sensitive instrument, or a crack in one of his own high-power lenses, would not be more disturbing than a strong emotion in a nature such as his. And yet there was but one woman to him, and that woman was the late Irene Adler, of dubious and questionable memory.');

-- Seed Borrowings
-- Borrowing 1: Alex Morgan borrowed Clean Code Copy 3. Issued 5 days ago, due in 9 days. Active!
-- Borrowing 2: Alex Morgan borrowed 1984 Copy 1. Issued 20 days ago, due 6 days ago! OVERDUE!
-- Borrowing 3: Emma Watson borrowed Pride and Prejudice Copy 2. Issued 3 days ago, due in 11 days.
-- Borrowing 4: Emma Watson previously borrowed The Great Gatsby Copy 1 and returned it yesterday.
INSERT INTO `borrowings` (`id`, `user_id`, `book_copy_id`, `issue_date`, `due_date`, `return_date`, `status`, `issued_by`, `received_by`, `remarks`) VALUES
(1, 3, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), NULL, 'Issued', 2, NULL, 'Standard member loan for study project'),
(2, 3, 10, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY), NULL, 'Overdue', 2, NULL, 'Overdue by 6 days - fine accruing'),
(3, 4, 7, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), NULL, 'Issued', 2, NULL, 'Literature term paper reading'),
(4, 4, 4, DATE_SUB(CURDATE(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Returned', 2, 2, 'Returned 3 days late, fine settled');

-- Seed Fines
-- For Borrowing 2 (Overdue by 6 days @ $5/day = $30.00 unpaid fine)
-- For Borrowing 4 (Returned 3 days late @ $5/day = $15.00, paid in cash)
INSERT INTO `fines` (`id`, `borrowing_id`, `user_id`, `fine_amount`, `paid_amount`, `status`, `fine_date`, `paid_date`, `payment_method`, `receipt_no`, `notes`) VALUES
(1, 2, 3, 30.00, 0.00, 'Unpaid', DATE_SUB(CURDATE(), INTERVAL 6 DAY), NULL, NULL, NULL, 'Late return fine accumulated over 6 overdue days'),
(2, 4, 4, 15.00, 15.00, 'Paid', DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Cash', 'REC-2026-0089', 'Fine settled at circulation desk upon return');

-- Seed Reservations
-- Emma Watson placed a reservation for 1984 (Book 5, all copies currently borrowed)
INSERT INTO `reservations` (`id`, `user_id`, `book_id`, `reservation_date`, `expiry_date`, `queue_position`, `status`, `notified_at`) VALUES
(1, 4, 5, DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL, 1, 'Pending', NULL);

-- Seed Reading Progress (Alex Morgan is reading Clean Code Chapter 2, 65%)
INSERT INTO `reading_progress` (`id`, `user_id`, `book_id`, `current_chapter`, `current_page`, `progress_percentage`, `last_read_at`) VALUES
(1, 3, 1, 2, 24, 65.00, NOW()),
(2, 4, 3, 1, 8, 30.00, NOW());

-- Seed Bookmarks
INSERT INTO `bookmarks` (`user_id`, `book_id`, `chapter_number`, `page_number`, `title`, `note`) VALUES
(3, 1, 2, 12, 'Boy Scout Rule Principle', 'Remember to apply this on every pull request refactoring!'),
(4, 3, 1, 4, 'Mr. Bennet Wit', 'Hilarious opening dialogue between Mr. and Mrs. Bennet.');

-- Seed Notes
INSERT INTO `notes` (`user_id`, `book_id`, `chapter_number`, `page_number`, `content`) VALUES
(3, 1, 2, 15, 'Key take away: Disinformation in variable names degrades readability over time.'),
(4, 3, 1, 5, 'Netherfield park introduction establishes social hierarchy in rural England.');

-- Seed Reviews
INSERT INTO `reviews` (`user_id`, `book_id`, `rating`, `review_text`, `status`, `created_at`) VALUES
(3, 1, 5, 'Every computer science student and software engineer must read this book. It completely transformed how I structure functions and naming conventions.', 'Approved', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(4, 3, 5, 'An absolute classic. The dry wit and banter between Elizabeth and Darcy never gets old.', 'Approved', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 4, 'Evocative imagery of the roaring twenties and the hollowness of the American dream.', 'Approved', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Seed Wishlists
INSERT INTO `wishlists` (`user_id`, `book_id`, `added_at`) VALUES
(3, 6, NOW()),
(4, 2, NOW());

-- Seed Notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `link`) VALUES
(3, 'Welcome to OpenShelf', 'Welcome to OpenShelf Library! Explore thousands of books and digital titles.', 'general', 1, 'catalogue.php'),
(3, 'Overdue Notice: 1984', 'Your borrowed copy of 1984 is overdue by 6 days. Outstanding fine: $30.00. Please return to desk.', 'overdue', 0, 'borrowed-books.php'),
(4, 'Reservation Confirmed: 1984', 'You are currently #1 in queue for 1984. You will be notified as soon as a copy is returned.', 'reservation', 0, 'reservations.php'),
(4, 'Fine Settled Receipt', 'Your fine payment of $15.00 for The Great Gatsby was recorded successfully.', 'fine', 1, 'borrowed-books.php');

-- Seed Audit Logs
INSERT INTO `audit_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES
(1, 'SYSTEM_INITIALIZE', 'System installed and database schema initialized successfully', '127.0.0.1'),
(1, 'CATALOGUE_SEEDED', 'Initial 6 books and digital chapters populated', '127.0.0.1'),
(2, 'BOOK_CIRCULATION', 'Issued copy CP-CC-03 to student Alex Morgan', '127.0.0.1'),
(2, 'FINE_COLLECTED', 'Settled $15.00 overdue fee for Emma Watson', '127.0.0.1');
