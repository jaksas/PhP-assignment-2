CREATE TABLE video (
	id int AUTO_INCREMENT PRIMARY KEY,
	name varchar(255) NOT NULL UNIQUE,
	category varchar(255), 
	length int UNSIGNED,
	rented boolean DEFAULT false NOT NULL
) Engine = InnoDB