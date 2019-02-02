rename table phpsessionflash to user_flash;

alter table user_flash
  add UserID int default 0 not null first;

alter table user_flash drop column SessionID;

TRUNCATE user_flash;

alter table user_flash
  add constraint phpsessionflash_pk
    primary key (UserID);



