set foreign_key_checks=0;

DROP DATABASE IF EXISTS bookstore_source;
CREATE DATABASE IF NOT EXISTS bookstore_source /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_polish_ci */;
USE bookstore_source;

DROP TABLE IF EXISTS authors;
CREATE TABLE IF NOT EXISTS authors (
  author_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  first_name varchar(50) COLLATE utf8_polish_ci NOT NULL,
  last_name varchar(50) COLLATE utf8_polish_ci,
  birth_date date DEFAULT NULL,
  PRIMARY KEY (author_id)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DELETE FROM authors;
INSERT INTO authors (author_id, first_name, last_name, birth_date) VALUES
	(1, 'Matthew ', 'Normani', '1982-06-06'),
	(2, 'Roji', 'Normani', '1977-03-15'),
	(3, 'Anna', 'Kowalska', '1999-05-15');

DROP TABLE IF EXISTS authors_active;
CREATE TABLE IF NOT EXISTS authors_active (
  author_id int(10) unsigned NOT NULL,
  type_id int(10) unsigned NOT NULL,
  last_name varchar(50),
  first_name varchar(50) DEFAULT 'no name',
  birth_date TIMESTAMP,
  PRIMARY KEY (author_id, type_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS books;
CREATE TABLE IF NOT EXISTS books (
  book_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(1024) COLLATE utf8_polish_ci NOT NULL,
  release_date date DEFAULT NULL,
  format_id int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (book_id),
  KEY books_format (format_id),
  CONSTRAINT books_format FOREIGN KEY (format_id) REFERENCES dictionary_values (id)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DELETE FROM books;
INSERT INTO books (book_id, name, release_date, format_id) VALUES
	(1, 'Learning PHP, MySQL & JavaScript: With jQuery, CSS & HTML5', NULL, NULL),
	(2, 'We\'re All Damaged', NULL, NULL),
	(3, 'JavaScript and JQuery: Interactive Front-End Web Development', '2016-06-06', NULL);

DROP TABLE IF EXISTS books_authors;
CREATE TABLE IF NOT EXISTS books_authors (
  book_id int(10) unsigned NOT NULL,
  author_id int(10) unsigned NOT NULL,
  PRIMARY KEY (book_id,author_id),
  KEY books_authors_author (author_id),
  CONSTRAINT books_authors_author FOREIGN KEY (author_id) REFERENCES authors (author_id),
  CONSTRAINT books_authors_book FOREIGN KEY (book_id) REFERENCES books (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DELETE FROM books_authors;
INSERT INTO books_authors (book_id, author_id) VALUES
	(1, 1),
	(2, 1),
	(3, 1),
	(2, 2),
	(3, 3);

-- authors_released_books
DROP VIEW IF EXISTS authors_released_books;
CREATE VIEW authors_released_books AS
SELECT
	a.author_id,
	count(ba.book_id) as numbers_of_books
FROM authors a
inner join books_authors ba
	on ba.author_id = a.author_id
group by a.author_id;

DROP TRIGGER IF EXISTS authors_bi;

DELIMITER $$
CREATE TRIGGER authors_bi
BEFORE INSERT ON authors
FOR EACH ROW
BEGIN
	IF new.first_name = "Jan" THEN
		set new.first_name = "Jan 1";
	END IF;
END $$
DELIMITER ;
