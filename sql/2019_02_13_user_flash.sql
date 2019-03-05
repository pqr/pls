TRUNCATE user_flash;
alter table user_flash drop primary key;
alter table user_flash
  add ID int first;
alter table user_flash
  add constraint user_flash_pk
    primary key (ID);
alter table user_flash modify ID int auto_increment;





