create table docsinventory
(
  ID int auto_increment,
  CreatedBy int not null,
  CreatedDateTime TIMESTAMP default CURRENT_TIMESTAMP not null,
  SentDate DATE not null,
  Comments TEXT not null,
  constraint docsinventory_pk
    primary key (ID)
);

CREATE TABLE `docsinventoryline` (
                                   `ID` int(11) NOT NULL AUTO_INCREMENT,
                                   `CreatedBy` int(11) NOT NULL,
                                   `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
                                   `DocsInventoryID` int(11) NOT NULL,
                                   `FileNumber` bigint(20) DEFAULT NULL,
                                   `Comments` varchar(500) NOT NULL,
                                   PRIMARY KEY (`ID`),
                                   KEY `docsinventoryline_DocsInventoryID_FileNumber_index` (`DocsInventoryID`,`FileNumber`),
                                   KEY `docsinventoryline_FileNumber_DocsInventoryID_index` (`FileNumber`,`DocsInventoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

alter table docsinventoryline
  add MAWBNumber VARCHAR(100) not null after DocsInventoryID;

