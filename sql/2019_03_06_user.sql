alter table user drop column EmailPassword;

alter table user
  add EmailSign TEXT not null;

alter table user drop column ResponsibleRoleIDs;

alter table user drop column CurrentVisitDateTime;

alter table user drop column LastVisitDateTime;

alter table user drop column LastRefreshTime;

