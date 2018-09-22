CREATE TABLE log_autolaunch_text
(
  ID int PRIMARY KEY NOT NULL,
  Report LONGTEXT NOT NULL
);

ALTER TABLE log_autolaunch DROP Report;