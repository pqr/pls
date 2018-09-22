CREATE TABLE log_email_text
(
  ID int PRIMARY KEY NOT NULL,
  Body MEDIUMTEXT NOT NULL
);

insert into log_email_text (ID, Body) SELECT ID, Body FROM log_email;

ALTER TABLE log_email DROP Body;