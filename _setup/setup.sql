
CREATE TABLE tbl_user (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(128) NOT NULL,
  is_admin INT(1) NOT NULL,
  insert_timestamp  DATETIME NOT NULL,
  update_timestamp  DATETIME NOT NULL,
  cnt_update INT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (user_id),
  UNIQUE KEY LOWER(email)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE tbl_user_remember(
  user_id INT UNSIGNED NOT NULL,
  db_token CHAR(150) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  insert_timestamp  DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES tbl_user (user_id),
  INDEX db_token (db_token(10))
) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL;

CREATE TABLE tbl_user_forgotten(
  user_id INT UNSIGNED NOT NULL,
  db_token CHAR(150) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  insert_timestamp  DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES tbl_user (user_id),
  INDEX db_token (db_token(10))
) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL;

CREATE TABLE tbl_user_login_bruteforce(
  email VARCHAR(255) NOT NULL,
  insert_timestamp  DATETIME NOT NULL,
  INDEX public (insert_timestamp)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

