create table folderstatus
(
  ModuleID int not null,
  RecordID int not null,
  HasFiles TINYINT(1) default 0 not null,
  constraint folderstatus_pk
    primary key (ModuleID, RecordID)
);

DROP TABLE cache_notemptyfilesfolder;

