alter table csexpenses modify Quantity decimal(11,2) not null;
alter table csincome modify Quantity decimal(11,2) not null;

alter table cinvoiceline modify Quantity decimal(11,2) not null;
alter table vinvoiceline modify Quantity decimal(11,2) not null;
