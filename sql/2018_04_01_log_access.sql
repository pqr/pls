CREATE TABLE `log_access` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreatedDate` date NOT NULL DEFAULT curdate(),
  `ModuleID` int(11) NOT NULL,
  `IsPOST` tinyint(1) NOT NULL DEFAULT 0,
  `IsAjax` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `CreatedByCreatedDate` (`CreatedBy`,`CreatedDate`,`ModuleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `user` ADD COLUMN `Mobile` VARCHAR(50) NOT NULL;
ALTER TABLE `user` ADD COLUMN `Sex` ENUM('','m','f') NOT NULL;
ALTER TABLE `reportaccess` ADD COLUMN `GroupIDs` VARCHAR(100) NOT NULL;
