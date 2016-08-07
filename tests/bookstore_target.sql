set foreign_key_checks=0;

DROP DATABASE IF EXISTS bookstore_target;
CREATE DATABASE IF NOT EXISTS bookstore_target /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_polish_ci */;
USE bookstore_target;

DROP TABLE IF EXISTS authors_types;
CREATE TABLE IF NOT EXISTS authors_types (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(50) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

DELETE FROM authors_types;
INSERT INTO authors_types (name) VALUES
	('active'),
	('nonactive');

DROP TABLE IF EXISTS authors;
CREATE TABLE IF NOT EXISTS authors (
  author_id int(10) unsigned NOT NULL,
  type_id int(10) unsigned NOT NULL,
  last_name varchar(50),
  first_name varchar(50) DEFAULT 'no name',
  birth_date TIMESTAMP,
  PRIMARY KEY (author_id, type_id)
) ENGINE=InnoDB;

ALTER TABLE authors
ADD CONSTRAINT authors_fk1 FOREIGN KEY (type_id) REFERENCES authors_types(id);

DELETE FROM authors;
INSERT INTO authors (author_id, type_id, first_name, last_name, birth_date) VALUES
	(1, 1, 'Matthew ', 'Normani', '1982-06-06'),
	(2, 1, 'Roji', 'Normani', '1977-03-15'),
	(3, 2, 'Anna', 'Kowalska', '1999-05-15');

DROP TABLE IF EXISTS books;
CREATE TABLE IF NOT EXISTS books (
  book_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(250) DEFAULT 'no name',
  format_id int(10) unsigned DEFAULT NULL,
  release_date date DEFAULT NULL,
  PRIMARY KEY (book_id),
  CONSTRAINT books_format FOREIGN KEY (format_id) REFERENCES dictionary_values (id) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB;

ALTER TABLE books
ADD UNIQUE INDEX unique_books_name (name);

ALTER TABLE books
ADD INDEX key_books_name (name, format_id);

DELETE FROM books;
INSERT INTO books (book_id, name, release_date, format_id) VALUES
	(1, 'Learning PHP, MySQL & JavaScript: With jQuery, CSS & HTML5', NULL, NULL),
	(2, 'We\'re All Damaged', NULL, NULL),
	(3, 'JavaScript and JQuery: Interactive Front-End Web Development', '2016-06-06', NULL);

DROP TABLE IF EXISTS books_authors;
CREATE TABLE IF NOT EXISTS books_authors (
  book_id int(10) unsigned NOT NULL,
  author_id int(10) unsigned NOT NULL,
  type_id int(10) unsigned NOT NULL,
  PRIMARY KEY (book_id,author_id, type_id),
  CONSTRAINT books_authors_author FOREIGN KEY (author_id, type_id) REFERENCES authors (author_id, type_id),
  CONSTRAINT books_authors_book FOREIGN KEY (book_id) REFERENCES books (book_id)
) ENGINE=InnoDB;

DELETE FROM books_authors;
INSERT INTO books_authors (book_id, author_id, type_id) VALUES
	(1, 1, 1),
	(2, 1, 1),
	(3, 1, 1),
	(2, 2, 1),
	(3, 3, 2);

DROP TABLE IF EXISTS `books_authors_archive`;
CREATE TABLE `books_authors_archive` (
  `book_id` int(10) unsigned NOT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`book_id`,`author_id`,`type_id`),
  KEY `books_authors_archive_author` (`author_id`,`type_id`),
  CONSTRAINT `books_authors_archive_author` FOREIGN KEY (`author_id`, `type_id`) REFERENCES `authors` (`author_id`, `type_id`),
  CONSTRAINT `books_authors_archive_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
) ENGINE=InnoDB;

DROP VIEW IF EXISTS authors_released_books;
CREATE VIEW authors_released_books AS
SELECT
	a.author_id,
	a.type_id,
	count(ba.book_id) as numbers_of_books
FROM authors a
inner join books_authors ba
	on ba.author_id = a.author_id
	AND ba.type_id = a.type_id
group by a.author_id, a.type_id;

DROP VIEW IF EXISTS authors_books;
CREATE VIEW authors_books AS
SELECT
	a.author_id,
	a.type_id,
	b.name
FROM authors a
inner join books_authors ba
	on ba.author_id = a.author_id
	AND ba.type_id = a.type_id
INNER JOIN books b
	ON b.book_id = ba.book_id
DELIMITER ;

DROP TRIGGER IF EXISTS authors_bi;
DELIMITER $$
CREATE TRIGGER authors_bi
BEFORE UPDATE ON authors
FOR EACH ROW
BEGIN
	IF new.first_name = "Jan" THEN
		set new.first_name = "Jan 1";
	END IF;
END $$
DELIMITER ;

DROP TRIGGER IF EXISTS books_bi;
DELIMITER $$
CREATE TRIGGER books_bi
BEFORE UPDATE ON books
FOR EACH ROW
BEGIN
	IF new.name = "Jan" THEN
		set new.name = "Jan 1";
	END IF;
END $$
DELIMITER ;

DROP TRIGGER IF EXISTS books_authors_archive_bi;
DELIMITER $$
CREATE TRIGGER books_authors_archive_bi
BEFORE UPDATE ON books_authors_archive
FOR EACH ROW
BEGIN
	set @a = 1;
END $$
DELIMITER ;
