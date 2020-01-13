CREATE TABLE `currencyrate_today` (
                                      `CurrencyISO` char(3) NOT NULL,
                                      `RateToRub` decimal(11,4) DEFAULT NULL,
                                      `ValidDate` date DEFAULT NULL,
                                      PRIMARY KEY (`CurrencyISO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `uservacation` (
                                `ID` int(11) NOT NULL AUTO_INCREMENT,
                                `CreatedBy` int(11) NOT NULL DEFAULT '0',
                                `CreatedDateTime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                                `UserID` int(11) DEFAULT NULL,
                                `VacationType` tinyint(1) NOT NULL DEFAULT '1',
                                `AvailableRemote` tinyint(1) NOT NULL DEFAULT '0',
                                `AvailableMobile` tinyint(1) NOT NULL DEFAULT '0',
                                `Comments` varchar(1000) NOT NULL,
                                `ValidFrom` date NOT NULL,
                                `ValidUntil` date NOT NULL,
                                PRIMARY KEY (`ID`),
                                KEY `uservacation_UserID_index` (`UserID`),
                                KEY `uservacation_ValidFrom_ValidUntil_index` (`ValidFrom`,`ValidUntil`),
                                CONSTRAINT `uservacation_user_ID_fk` FOREIGN KEY (`UserID`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


alter table queue_email
    add customfrom VARCHAR(100) default '' null;

alter table log_email
    add customfrom VARCHAR(100) default '' null;

