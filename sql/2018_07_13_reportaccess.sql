CREATE TABLE reportaccess_user
(
  ID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  CreatedBy int NOT NULL,
  CreatedDateTime timestamp NOT NULL,
  ReportClass VARCHAR(300) NOT NULL,
  UserID int NOT NULL
);
CREATE INDEX UserIDReportClass ON reportaccess_user (UserID, ReportClass);

CREATE TABLE reportaccess_group
(
  ID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  CreatedBy int NOT NULL,
  CreatedDateTime timestamp NOT NULL,
  ReportClass VARCHAR(300) NOT NULL,
  GroupID int NOT NULL
);
CREATE INDEX GroupIDReportClass ON reportaccess_group (GroupID, ReportClass);

rename table `reportaccess` to `_delete_reportaccess`;

-- AUTOLAUNCH Light/MigrateReportAccess

