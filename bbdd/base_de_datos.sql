CREATE DATABASE IF NOT EXISTS curso_backfront;
USE curso_backfront;

CREATE TABLE user(
	id 			int(255) auto_increment not null,
	role		varchar(20),
	name		varchar(180),
	surname 	varchar(255),
	email		varchar(255),
	password 	varchar(255),
	created_at  datetime,
	CONSTRAINT pk_user PRIMARY KEY(id)
)ENGINE=InnoDb;

CREATE TABLE task(
	id 			int(255) auto_increment not null,
	user_id		int(255) not null,
	title		varchar(255),
	description	text,
	status		varchar(100),
	created_at  datetime,
	updated_at  datetime,
	CONSTRAINT pk_task PRIMARY KEY(id),
	CONSTRAINT fk_task_user FOREIGN KEY(user_id) REFERENCES user(id)
)ENGINE=InnoDb;
