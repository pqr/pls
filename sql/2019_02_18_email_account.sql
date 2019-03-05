alter table queue_email
  add EmailAccountID int default NULL null;

create table emailaccount
(
  ID int auto_increment,
  CreatedBy int not null,
  CreatedDateTime TIMESTAMP default CURRENT_TIMESTAMP not null,
  Host VARCHAR(300) not null,
  Port int not null,
  Encryption CHAR(3) default NULL null,
  Username VARCHAR(300) not null,
  Password VARCHAR(300) not null,
  constraint emailaccount_pk
    primary key (ID)
);

alter table log_email
  add EmailAccountID int default NULL null;

alter table queue_email drop column `From`;
