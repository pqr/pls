-- MySQL dump 10.16  Distrib 10.2.12-MariaDB, for osx10.13 (x86_64)
--
-- Host: localhost    Database: book4a_clean
-- ------------------------------------------------------
-- Server version	10.2.12-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `airfreightrate`
--

DROP TABLE IF EXISTS `airfreightrate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `airfreightrate` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `VendorID` int(11) NOT NULL,
  `AirlineAlphaCode` char(2) NOT NULL,
  `TransitTime` varchar(10) NOT NULL,
  `DepartureIATA` char(3) NOT NULL,
  `DepartureLocationName` varchar(100) NOT NULL,
  `ArrivalIATA` char(3) NOT NULL,
  `ArrivalLocationName` varchar(100) NOT NULL,
  `ValidFrom` date NOT NULL,
  `ValidUntil` date NOT NULL,
  `Comments` text NOT NULL,
  `MinAmount` decimal(10,2) NOT NULL,
  `AmountN` decimal(10,2) NOT NULL,
  `Amount45` decimal(10,2) NOT NULL,
  `Amount100` decimal(10,2) NOT NULL,
  `Amount200` decimal(10,2) NOT NULL,
  `Amount300` decimal(10,2) NOT NULL,
  `Amount500` decimal(10,2) NOT NULL,
  `Amount750` decimal(10,2) NOT NULL,
  `Amount1000` decimal(10,2) NOT NULL,
  `Amount2000` decimal(10,2) NOT NULL,
  `FSCAmount` decimal(10,2) NOT NULL,
  `SSCAmount` decimal(10,2) NOT NULL,
  `CurrencyISO` char(3) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4468 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `airfreightrate`
--

LOCK TABLES `airfreightrate` WRITE;
/*!40000 ALTER TABLE `airfreightrate` DISABLE KEYS */;
INSERT INTO `airfreightrate` VALUES (100,1,'2014-10-14 10:37:11','',24,'TK','2-3','HKG','Hong Kong Int\'l','VKO','Vnukovo','2014-10-13','2014-10-19','HKG-IST-VKO ; Dim.: 80*80*80cm/1 & GW. < 80.0Kgs',123.38,5.71,3.70,2.86,2.86,2.86,2.86,2.86,2.86,2.86,0.94,0.16,'USD');
/*!40000 ALTER TABLE `airfreightrate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `airfreightsurcharge`
--

DROP TABLE IF EXISTS `airfreightsurcharge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `airfreightsurcharge` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `VendorID` int(11) NOT NULL,
  `AirlineAlphaCode` char(2) NOT NULL,
  `IATA` char(3) NOT NULL,
  `LocationName` varchar(100) NOT NULL,
  `Incoterms` char(3) NOT NULL,
  `IncotermsLocation` varchar(100) NOT NULL,
  `ValidFrom` date NOT NULL,
  `ValidUntil` date NOT NULL,
  `Comments` text NOT NULL,
  `BaseAmount` decimal(10,2) NOT NULL,
  `MinAmount` decimal(10,2) NOT NULL,
  `MaxAmount` decimal(10,2) NOT NULL,
  `PerKgAmount` decimal(10,2) NOT NULL,
  `CurrencyISO` char(3) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `airfreightsurcharge`
--

LOCK TABLES `airfreightsurcharge` WRITE;
/*!40000 ALTER TABLE `airfreightsurcharge` DISABLE KEYS */;
INSERT INTO `airfreightsurcharge` VALUES (10,8,'2014-05-30 21:07:20','EDI  Fee',3,'','TLV','BEN GURION INTL','EXW','','2014-05-01','2099-01-01','',7.50,0.00,0.00,0.00,'USD');
/*!40000 ALTER TABLE `airfreightsurcharge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `airline`
--

DROP TABLE IF EXISTS `airline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `airline` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL DEFAULT '',
  `DigitalCode` char(3) NOT NULL DEFAULT '',
  `AlphaCode` char(2) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `airline`
--

LOCK TABLES `airline` WRITE;
/*!40000 ALTER TABLE `airline` DISABLE KEYS */;
INSERT INTO `airline` VALUES (100,19,'2015-08-07 06:46:48','AIR KORYO','','JS');
/*!40000 ALTER TABLE `airline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bu`
--

DROP TABLE IF EXISTS `bu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bu` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` char(3) NOT NULL,
  `OrderID` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bu`
--

LOCK TABLES `bu` WRITE;
/*!40000 ALTER TABLE `bu` DISABLE KEYS */;
INSERT INTO `bu` VALUES (1,0,'2013-04-26 08:11:51','MOW',1),(2,0,'2013-04-26 08:11:51','LED',2);
/*!40000 ALTER TABLE `bu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_notemptyfilesfolder`
--

DROP TABLE IF EXISTS `cache_notemptyfilesfolder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_notemptyfilesfolder` (
  `ModuleName` varchar(100) NOT NULL DEFAULT '',
  `RecordID` int(11) NOT NULL,
  PRIMARY KEY (`ModuleName`,`RecordID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_notemptyfilesfolder`
--

LOCK TABLES `cache_notemptyfilesfolder` WRITE;
/*!40000 ALTER TABLE `cache_notemptyfilesfolder` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_notemptyfilesfolder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ccontact`
--

DROP TABLE IF EXISTS `ccontact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ccontact` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `CustomerID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Position` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `WorkPhone` varchar(100) NOT NULL,
  `MobilePhone` varchar(100) NOT NULL,
  `Comments` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ccontact`
--

LOCK TABLES `ccontact` WRITE;
/*!40000 ALTER TABLE `ccontact` DISABLE KEYS */;
/*!40000 ALTER TABLE `ccontact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cinvoice`
--

DROP TABLE IF EXISTS `cinvoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cinvoice` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  `CompanyID` tinyint(1) NOT NULL DEFAULT 1,
  `CustomerID` int(6) NOT NULL,
  `CustomerAccountID` int(7) NOT NULL,
  `Status` tinyint(2) NOT NULL,
  `DocType` tinyint(2) NOT NULL DEFAULT 1,
  `Comments` text NOT NULL,
  `DocDate` date NOT NULL,
  `OneCDocDate` date NOT NULL,
  `DueDate` date NOT NULL,
  `CacheFirstPaymentDate` date DEFAULT NULL,
  `CacheLastPaymentDate` date DEFAULT NULL,
  `CachePaymentsTotalAmount` decimal(11,2) DEFAULT NULL,
  `CachePaymentsReference` text DEFAULT NULL,
  `CacheOutstandingAmount` decimal(11,2) NOT NULL,
  `InvoiceTo` text NOT NULL,
  `ShippingDocument` text NOT NULL,
  `ShipmentReference` varchar(100) NOT NULL DEFAULT '',
  `ConsigneeReference` varchar(100) NOT NULL DEFAULT '',
  `PaymentTerms` varchar(100) NOT NULL,
  `Pieces` int(11) NOT NULL,
  `GrossWeight` decimal(9,2) NOT NULL,
  `ChargeableWeight` decimal(9,2) NOT NULL,
  `Incoterms` char(3) NOT NULL,
  `IncotermsLocation` varchar(100) NOT NULL,
  `DepartureLocationName` varchar(100) NOT NULL,
  `ArrivalLocationName` varchar(100) NOT NULL,
  `OurReference` varchar(100) NOT NULL,
  `Amount` decimal(9,2) NOT NULL,
  `CurrencyISO` char(3) CHARACTER SET ascii NOT NULL,
  `PaymentCurrencyISO` char(3) NOT NULL DEFAULT 'RUB',
  `RatePercent` varchar(10) NOT NULL,
  `CacheLinesFileNumber` text DEFAULT NULL,
  `CopyRequestReceived` tinyint(1) NOT NULL,
  `CopyInvoiceReceived` tinyint(1) NOT NULL,
  `CopyActReceived` tinyint(1) NOT NULL,
  `CopyFakturaReceived` tinyint(1) NOT NULL,
  `OriginalRequestReceived` tinyint(1) NOT NULL,
  `OriginalInvoiceReceived` tinyint(1) NOT NULL,
  `OriginalActReceived` tinyint(1) NOT NULL,
  `OriginalFakturaReceived` tinyint(1) NOT NULL,
  `CActName` varchar(100) NOT NULL,
  `CActDate` date NOT NULL,
  `CActAmount` decimal(11,2) NOT NULL,
  `BankID` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerID` (`CustomerID`,`Amount`),
  KEY `CustomerAccountID` (`CustomerAccountID`,`Amount`),
  KEY `CompanyIDBankID` (`CompanyID`,`BankID`)
) ENGINE=InnoDB AUTO_INCREMENT=12714 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cinvoice`
--

LOCK TABLES `cinvoice` WRITE;
/*!40000 ALTER TABLE `cinvoice` DISABLE KEYS */;
INSERT INTO `cinvoice` VALUES (8508,12,'2016-07-15 06:56:59','1065-07-2016',1,430,546,22,1,'','2016-07-12','0000-00-00','2016-07-20','2016-07-20','2016-07-20',1553.90,'1051-06-2016 1065-07-2016',0.00,'INSTAR LOGISTICS\nAGREEMENT PLS/IN/2010.04.02','580-09402083','','','5 DAYS',2,130.27,141.00,'EXW','ITALY','SEGRATE','DOMODEDOVO','',1553.90,'EUR','RUB','','106510',0,1,0,0,0,0,0,0,'','0000-00-00',0.00,1),(8528,2,'2016-07-19 22:04:57','8528',2,93,512,22,1,'','2016-07-20','0000-00-00','2016-07-27','2016-07-29','2016-07-29',1500.00,'8528',0.00,'MEROBERG LP\nSUITE 4053, 15 BELL STREET, \nST ANDREWS, FIFE, \nSCOTLAND, KY16 9UR\nUK','176-65621216','','','7 DAYS',64,565.00,565.00,'FCA','LAHORE PAKISTAN','LAHORE','DOMODEDOVO','',1500.00,'USD','RUB','','106454',0,0,0,0,0,0,0,0,'','0000-00-00',0.00,2),(9093,7,'2016-10-17 08:19:00','332',4,348,410,22,1,'','2016-10-17','0000-00-00','2016-10-22','2016-10-17','2016-10-17',768328.48,'332',0.00,'CARDIOMEDICS OOO\nPOKROVSKY BULV., 4/17, BLD. 3, 101000,\nMOSCOW, RUSSIA','580-11486823','','','5 DAYS',15,1850.00,4471.00,'EXW','KERKRADE','AMSTERDAM-SCHIPHOL','SHEREMETYEVO','',768328.48,'RUB','RUB','3%','106944',0,1,0,0,0,0,0,0,'','0000-00-00',0.00,4),(9104,7,'2016-10-18 11:37:51','017-0916',1,447,569,22,1,'','2016-09-15','0000-00-00','2016-10-28','2016-10-18','2016-10-18',4309.32,'171016 VDA UT project',0.00,'UNITRANSPORTS INTL. FORWARDERS S.R.L.\nVIA ZURIGO, 28/2 20147 MILAN ITALY','580-11030618','','','10 DAYS',0,10324.00,10324.00,'','','MALPENSA','SHEREMETYEVO','',4309.32,'EUR','RUB','','106962',0,1,0,0,0,0,0,0,'','0000-00-00',0.00,1);
/*!40000 ALTER TABLE `cinvoice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cinvoice_file_amount`
--

DROP TABLE IF EXISTS `cinvoice_file_amount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cinvoice_file_amount` (
  `CInvoiceID` int(11) NOT NULL,
  `FileNumber` bigint(20) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `AmountinUSD` decimal(10,2) NOT NULL,
  PRIMARY KEY (`FileNumber`,`CInvoiceID`),
  KEY `CInvoiceID` (`CInvoiceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Sum of cinvoiceline.Amount per FileNumber';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cinvoice_file_amount`
--

LOCK TABLES `cinvoice_file_amount` WRITE;
/*!40000 ALTER TABLE `cinvoice_file_amount` DISABLE KEYS */;
/*!40000 ALTER TABLE `cinvoice_file_amount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cinvoiceline`
--

DROP TABLE IF EXISTS `cinvoiceline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cinvoiceline` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  `FileNumber` bigint(20) NOT NULL,
  `CInvoiceID` int(11) NOT NULL,
  `UOMID` int(11) NOT NULL,
  `Quantity` decimal(11,1) NOT NULL,
  `Price` decimal(11,2) NOT NULL,
  `Amount` decimal(11,2) NOT NULL,
  `VInvoiceName` varchar(50) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerInvoiceID` (`CInvoiceID`),
  KEY `FileNumber` (`FileNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=28168 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cinvoiceline`
--

LOCK TABLES `cinvoiceline` WRITE;
/*!40000 ALTER TABLE `cinvoiceline` DISABLE KEYS */;
INSERT INTO `cinvoiceline` VALUES (22544,12,'2016-07-15 06:56:59','Airfreight',106510,8508,1,1.0,1553.90,1553.90,''),(22592,2,'2016-07-19 22:04:57','Airfreight',106454,8528,2,1.0,1500.00,1500.00,''),(23527,7,'2016-10-17 08:19:00','Транспортно-экспедиторское обслуживание по маршруту: KERKRADE - AMSTERDAM-SCHIPHOL - SHEREMETYEVO',106944,9093,1,1.0,768328.48,768328.48,''),(23546,7,'2016-10-18 11:37:51','Profit share from airfreight forwarding services sale',106962,9104,1,1.0,4309.32,4309.32,'');
/*!40000 ALTER TABLE `cinvoiceline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `containertype`
--

DROP TABLE IF EXISTS `containertype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `containertype` (
  `ID` int(6) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `TEU` tinyint(2) NOT NULL DEFAULT 0,
  `OrderID` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `containertype`
--

LOCK TABLES `containertype` WRITE;
/*!40000 ALTER TABLE `containertype` DISABLE KEYS */;
INSERT INTO `containertype` VALUES (1,1,'2011-06-01 07:36:16','20\'',1,1),(2,1,'2011-06-01 07:36:24','40\'',2,2),(3,1,'2011-06-01 07:36:31','40\'HC',2,3),(4,7,'2012-11-13 12:39:38','LCL',1,0),(5,9,'2013-08-25 11:01:12','40\'RE',2,4);
/*!40000 ALTER TABLE `containertype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cpayment`
--

DROP TABLE IF EXISTS `cpayment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cpayment` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `CompanyID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `CustomerAccountID` int(11) NOT NULL,
  `Amount` decimal(11,2) NOT NULL,
  `CurrencyISO` char(3) NOT NULL,
  `PaymentDate` date NOT NULL,
  `Comments` text NOT NULL,
  `CacheOverpayAmount` decimal(11,2) NOT NULL COMMENT 'Переплата: cpayment.Amount - SUM(cpayment_cinvoice.Amount)',
  `OverpayCleared` tinyint(1) NOT NULL,
  `OneCGUID` char(36) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7979 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cpayment`
--

LOCK TABLES `cpayment` WRITE;
/*!40000 ALTER TABLE `cpayment` DISABLE KEYS */;
INSERT INTO `cpayment` VALUES (200,9,'2012-09-13 14:15:08','linux_200812',2,32,32,39332.14,'USD','2012-09-13','',0.00,0,'');
/*!40000 ALTER TABLE `cpayment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cpayment_cinvoice`
--

DROP TABLE IF EXISTS `cpayment_cinvoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cpayment_cinvoice` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `CPaymentID` int(11) NOT NULL,
  `CInvoiceID` int(11) NOT NULL,
  `CInvoiceName` varchar(100) NOT NULL,
  `ClearingAmountInInvoiceCurrency` decimal(11,2) NOT NULL,
  `RateToInvoiceCurrency` decimal(12,4) NOT NULL,
  `ClearingAmountInPaymentCurrency` decimal(11,2) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CPaymentID` (`CPaymentID`),
  KEY `CInvoiceID` (`CInvoiceID`)
) ENGINE=InnoDB AUTO_INCREMENT=12965 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cpayment_cinvoice`
--

LOCK TABLES `cpayment_cinvoice` WRITE;
/*!40000 ALTER TABLE `cpayment_cinvoice` DISABLE KEYS */;
INSERT INTO `cpayment_cinvoice` VALUES (427,9,'2012-09-13 14:15:08',200,659,'659',1892.49,1.0000,1892.49),(428,9,'2012-09-13 14:15:08',200,673,'673',785.00,1.0000,785.00),(429,9,'2012-09-13 14:15:08',200,679,'679',765.00,1.0000,765.00),(430,9,'2012-09-13 14:15:08',200,680,'680',2030.00,1.0000,2030.00),(431,9,'2012-09-13 14:15:08',200,681,'681',811.70,1.0000,811.70),(432,9,'2012-09-13 14:15:08',200,682,'682',1290.00,1.0000,1290.00),(433,9,'2012-09-13 14:15:08',200,691,'691',445.91,1.0000,445.91),(434,9,'2012-09-13 14:15:08',200,718,'717',372.84,1.0000,372.84),(435,9,'2012-09-13 14:15:08',200,719,'719',372.84,1.0000,372.84),(436,9,'2012-09-13 14:15:08',200,722,'722',1039.84,1.0000,1039.84),(437,9,'2012-09-13 14:15:08',200,723,'723',522.20,1.0000,522.20),(438,9,'2012-09-13 14:15:08',200,728,'728',1021.10,1.0000,1021.10),(439,9,'2012-09-13 14:15:08',200,729,'729',2486.70,1.0000,2486.70),(440,9,'2012-09-13 14:15:08',200,732,'732',759.00,1.0000,759.00),(441,9,'2012-09-13 14:15:08',200,738,'738',2232.72,1.0000,2232.72),(442,9,'2012-09-13 14:15:08',200,740,'740',22504.80,1.0000,22504.80);
/*!40000 ALTER TABLE `cpayment_cinvoice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `csexpenses`
--

DROP TABLE IF EXISTS `csexpenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `csexpenses` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  `ShipmentID` int(11) NOT NULL,
  `Quantity` decimal(11,1) NOT NULL,
  `Price` decimal(9,2) NOT NULL,
  `Amount` decimal(9,2) NOT NULL,
  `CurrencyISO` char(3) CHARACTER SET ascii NOT NULL,
  `InvNum` varchar(100) NOT NULL,
  `HtmlColor` char(6) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ShipmentID` (`ShipmentID`)
) ENGINE=InnoDB AUTO_INCREMENT=35452 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csexpenses`
--

LOCK TABLES `csexpenses` WRITE;
/*!40000 ALTER TABLE `csexpenses` DISABLE KEYS */;
INSERT INTO `csexpenses` VALUES (23323,12,'2016-02-24 08:05:13','Airfreight',7773,397.0,1.65,655.05,'EUR','599824',''),(23324,12,'2016-02-24 08:05:13','Handling fee',7773,1.0,22.00,22.00,'EUR','599824',''),(23325,12,'2016-02-24 08:05:13','RAF Fee',7773,1.0,4.50,4.50,'EUR','599824',''),(23326,12,'2016-02-24 08:05:13','Terminal Handling',7773,397.0,0.12,47.64,'EUR','599824',''),(23327,12,'2016-02-24 08:05:13','X-Ray Fee',7773,397.0,0.10,39.70,'EUR','599824',''),(23328,12,'2016-02-24 08:05:13','Pick Up',7773,1.0,200.00,200.00,'EUR','599824',''),(23329,12,'2016-02-24 08:05:13','Profit Share',7773,397.0,0.20,79.40,'EUR','599824',''),(23330,12,'2016-02-24 13:01:49','Cool check fee',7773,1.0,54.00,54.00,'EUR','599824',''),(23331,12,'2016-02-24 13:01:49','cool w/h airline',7773,1.0,120.00,120.00,'EUR','599824','');
/*!40000 ALTER TABLE `csexpenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cshistory`
--

DROP TABLE IF EXISTS `cshistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cshistory` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `ShipmentID` int(11) NOT NULL,
  `EGPinUSD` decimal(9,2) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ShipmentID` (`ShipmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cshistory`
--

LOCK TABLES `cshistory` WRITE;
/*!40000 ALTER TABLE `cshistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `cshistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `csincome`
--

DROP TABLE IF EXISTS `csincome`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `csincome` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  `ShipmentID` int(11) NOT NULL,
  `Quantity` decimal(11,1) NOT NULL,
  `Price` decimal(9,2) NOT NULL,
  `Amount` decimal(9,2) NOT NULL,
  `CurrencyISO` char(3) CHARACTER SET ascii NOT NULL,
  `InvNum` varchar(100) NOT NULL,
  `HtmlColor` char(6) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ShipmentID` (`ShipmentID`)
) ENGINE=InnoDB AUTO_INCREMENT=30061 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csincome`
--

LOCK TABLES `csincome` WRITE;
/*!40000 ALTER TABLE `csincome` DISABLE KEYS */;
INSERT INTO `csincome` VALUES (19378,12,'2016-02-25 08:26:15','Airfreight',7773,397.0,1.55,615.35,'EUR','70/2016',''),(19379,12,'2016-02-25 08:26:15','Fuel surcharge',7773,397.0,1.27,504.19,'EUR','70/2016',''),(19380,12,'2016-02-25 08:26:15','Handling fee',7773,1.0,26.50,26.50,'EUR','70/2016',''),(19381,12,'2016-02-25 08:26:16','Terminal Handling Charge',7773,397.0,0.15,59.55,'EUR','70/2016',''),(19382,12,'2016-02-25 08:26:16','Pick Up',7773,1.0,200.00,200.00,'EUR','70/2016',''),(19383,12,'2016-02-25 08:26:16','Agent commission',7773,397.0,0.15,59.55,'EUR','70/2016',''),(19384,12,'2016-02-25 08:26:16','X-Ray Fee',7773,397.0,0.10,39.70,'EUR','70/2016','');
/*!40000 ALTER TABLE `csincome` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currency`
--

DROP TABLE IF EXISTS `currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currency` (
  `ID` int(5) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(3) NOT NULL DEFAULT '',
  `Symbol` varchar(3) NOT NULL DEFAULT '',
  `Code` int(11) NOT NULL,
  `Description` varchar(100) NOT NULL DEFAULT '',
  `DescriptionRu` varchar(100) NOT NULL DEFAULT '',
  `OrderID` int(5) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currency`
--

LOCK TABLES `currency` WRITE;
/*!40000 ALTER TABLE `currency` DISABLE KEYS */;
INSERT INTO `currency` VALUES (1,2,'2010-05-17 12:01:45','USD','$',0,'','',1),(2,2,'2010-05-17 12:01:55','EUR','€',0,'','',2),(3,2,'2010-05-17 12:02:02','RUB','RUB',0,'','',3),(4,2,'2010-07-01 14:40:05','CNY','CNY',0,'','',4),(6,0,'2010-10-20 15:20:08','HKD','HKD',0,'','',5),(7,0,'2010-10-20 15:20:08','CHF','CHF',0,'','',6),(8,0,'2010-10-20 15:20:08','GBP','GBP',0,'','',7),(9,1,'2014-10-14 13:23:39','INR','INR',0,'INR','INR',10);
/*!40000 ALTER TABLE `currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencyrate`
--

DROP TABLE IF EXISTS `currencyrate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currencyrate` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `CurrencyISO` char(3) CHARACTER SET ascii NOT NULL,
  `RateToRUB` decimal(11,4) NOT NULL,
  `ValidDate` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `CurrencyID` (`CurrencyISO`,`ValidDate`)
) ENGINE=InnoDB AUTO_INCREMENT=11906 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencyrate`
--

LOCK TABLES `currencyrate` WRITE;
/*!40000 ALTER TABLE `currencyrate` DISABLE KEYS */;
INSERT INTO `currencyrate` VALUES (9431,0,'2017-01-17 09:00:00','GBP',72.0303,'2017-01-18'),(9432,0,'2017-01-17 09:00:00','USD',59.4015,'2017-01-18'),(9433,0,'2017-01-17 09:00:00','EUR',63.2864,'2017-01-18'),(9434,0,'2017-01-17 09:00:00','INR',0.8730,'2017-01-18'),(9435,0,'2017-01-17 09:00:00','CNY',8.6439,'2017-01-18'),(9436,0,'2017-01-17 09:00:00','CHF',59.0355,'2017-01-18');
/*!40000 ALTER TABLE `currencyrate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencyrate_citadele`
--

DROP TABLE IF EXISTS `currencyrate_citadele`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currencyrate_citadele` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` char(3) NOT NULL,
  `ValidDate` date NOT NULL,
  `BuyingForEUR` float NOT NULL,
  `SellingForEUR` float NOT NULL,
  `BuyingForUSD` float NOT NULL,
  `SellingForUSD` float NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Name_ValidDate` (`Name`,`ValidDate`)
) ENGINE=InnoDB AUTO_INCREMENT=5766 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencyrate_citadele`
--

LOCK TABLES `currencyrate_citadele` WRITE;
/*!40000 ALTER TABLE `currencyrate_citadele` DISABLE KEYS */;
INSERT INTO `currencyrate_citadele` VALUES (100,0,'2014-09-30 05:00:02','USD','2014-09-30',1.285,1.253,1,1);
/*!40000 ALTER TABLE `currencyrate_citadele` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer` (
  `ID` int(6) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  `CompanyID` int(11) NOT NULL,
  `Commodity` text NOT NULL,
  `ContactPersonName` varchar(300) NOT NULL,
  `NoticeEmail` varchar(300) NOT NULL,
  `Address` text NOT NULL,
  `Contacts` text NOT NULL,
  `DefaultConsignee` varchar(100) NOT NULL,
  `CreditPeriod` int(3) NOT NULL,
  `CreditAmount` int(9) NOT NULL,
  `CreditCurrencyISO` char(3) CHARACTER SET ascii NOT NULL COMMENT 'Credit Currency ISO',
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `BUIDs` varchar(10) NOT NULL,
  `AWBInstructions` text NOT NULL,
  `OtherInstructions` text NOT NULL,
  `UseDefaultAddon` int(11) NOT NULL DEFAULT 1,
  `AddonUSDMinAmount` decimal(11,2) NOT NULL,
  `AddonUSDAmountN` decimal(11,2) NOT NULL,
  `AddonUSDAmount45` decimal(11,2) NOT NULL,
  `AddonUSDAmount100` decimal(11,2) NOT NULL,
  `AddonUSDAmount200` decimal(11,2) NOT NULL,
  `AddonUSDAmount300` decimal(11,2) NOT NULL,
  `AddonUSDAmount500` decimal(11,2) NOT NULL,
  `AddonUSDAmount750` decimal(11,2) NOT NULL,
  `AddonUSDAmount1000` decimal(11,2) NOT NULL,
  `AddonUSDAmount2000` decimal(11,2) NOT NULL,
  `AddonEURMinAmount` decimal(11,2) NOT NULL,
  `AddonEURAmountN` decimal(11,2) NOT NULL,
  `AddonEURAmount45` decimal(11,2) NOT NULL,
  `AddonEURAmount100` decimal(11,2) NOT NULL,
  `AddonEURAmount200` decimal(11,2) NOT NULL,
  `AddonEURAmount300` decimal(11,2) NOT NULL,
  `AddonEURAmount500` decimal(11,2) NOT NULL,
  `AddonEURAmount750` decimal(11,2) NOT NULL,
  `AddonEURAmount1000` decimal(11,2) NOT NULL,
  `AddonEURAmount2000` decimal(11,2) NOT NULL,
  `CacheOpenInvoicesAmountUSD` decimal(9,2) NOT NULL COMMENT 'Задолженность по счетам выставленным в USD',
  `CacheOpenInvoicesAmountEUR` decimal(9,2) NOT NULL COMMENT 'Задолженность по счетам выставленным в EUR',
  `CacheOpenInvoicesAmountRUB` decimal(9,2) NOT NULL COMMENT 'Задолженность по счетам выставленным в RUB',
  `CacheOverdueInvoicesAmountUSD` decimal(9,2) NOT NULL,
  `CacheOverdueInvoicesAmountEUR` decimal(9,2) NOT NULL,
  `CacheOverdueInvoicesAmountRUB` decimal(9,2) NOT NULL,
  `CacheOpenPaymentsAmountUSD` decimal(9,2) NOT NULL COMMENT 'Переплаты из Customer Payments пришедшим в USD',
  `CacheOpenPaymentsAmountEUR` decimal(9,2) NOT NULL COMMENT 'Переплаты из Customer Payments пришедшим в EUR',
  `CacheOpenPaymentsAmountRUB` decimal(9,2) NOT NULL COMMENT 'Переплаты из Customer Payments пришедшим в RUB',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=608 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer`
--

LOCK TABLES `customer` WRITE;
/*!40000 ALTER TABLE `customer` DISABLE KEYS */;
INSERT INTO `customer` VALUES (100,7,'2012-02-17 06:55:48','TRADING HOUSE STANDART',0,'STONE','','','AVIATSIONNIY PER.5\nMOSCOW, 125167, \nRUSSIA','KUKHALSKAY E.V. \nTel: +7 (985) 1242662','TRADING HOUSE STANDART',0,0,'USD',1,'|1|','','',1,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00);
/*!40000 ALTER TABLE `customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_customeraccount`
--

DROP TABLE IF EXISTS `customer_customeraccount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_customeraccount` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `CustomerID` int(6) NOT NULL,
  `CustomerAccountID` int(7) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `CustomerID_CustomerAccountID` (`CustomerID`,`CustomerAccountID`)
) ENGINE=InnoDB AUTO_INCREMENT=884 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_customeraccount`
--

LOCK TABLES `customer_customeraccount` WRITE;
/*!40000 ALTER TABLE `customer_customeraccount` DISABLE KEYS */;
INSERT INTO `customer_customeraccount` VALUES (91,7,'2012-02-17 06:57:34',100,86);
/*!40000 ALTER TABLE `customer_customeraccount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_openinvoices`
--

DROP TABLE IF EXISTS `customer_openinvoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_openinvoices` (
  `CustomerID` int(11) NOT NULL,
  `CurrencyISO` char(3) NOT NULL DEFAULT '',
  `OpenAmount` decimal(9,2) NOT NULL,
  `OverdueAmount` decimal(9,2) NOT NULL,
  `OverdueAmount30` decimal(9,2) NOT NULL,
  `OverdueAmount3060` decimal(9,2) NOT NULL,
  `OverdueAmount6090` decimal(9,2) NOT NULL,
  `OverdueAmount90180` decimal(9,2) NOT NULL,
  `OverdueAmount180` decimal(9,2) NOT NULL,
  PRIMARY KEY (`CustomerID`,`CurrencyISO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_openinvoices`
--

LOCK TABLES `customer_openinvoices` WRITE;
/*!40000 ALTER TABLE `customer_openinvoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_openinvoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_openinvoicespercompany`
--

DROP TABLE IF EXISTS `customer_openinvoicespercompany`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_openinvoicespercompany` (
  `CustomerID` int(11) NOT NULL,
  `CurrencyISO` char(3) NOT NULL DEFAULT '',
  `CompanyID` int(11) NOT NULL,
  `OpenAmount` decimal(9,2) NOT NULL,
  `OverdueAmount` decimal(9,2) NOT NULL,
  `OverdueAmount30` decimal(9,2) NOT NULL,
  `OverdueAmount3060` decimal(9,2) NOT NULL,
  `OverdueAmount6090` decimal(9,2) NOT NULL,
  `OverdueAmount90180` decimal(9,2) NOT NULL,
  `OverdueAmount180` decimal(9,2) NOT NULL,
  PRIMARY KEY (`CustomerID`,`CurrencyISO`,`CompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_openinvoicespercompany`
--

LOCK TABLES `customer_openinvoicespercompany` WRITE;
/*!40000 ALTER TABLE `customer_openinvoicespercompany` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_openinvoicespercompany` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_openpayments`
--

DROP TABLE IF EXISTS `customer_openpayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_openpayments` (
  `CustomerID` int(11) NOT NULL,
  `CurrencyISO` char(3) NOT NULL DEFAULT '',
  `OpenPaymentsAmount` decimal(9,2) NOT NULL,
  PRIMARY KEY (`CustomerID`,`CurrencyISO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_openpayments`
--

LOCK TABLES `customer_openpayments` WRITE;
/*!40000 ALTER TABLE `customer_openpayments` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_openpayments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_responsible`
--

DROP TABLE IF EXISTS `customer_responsible`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_responsible` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `CustomerID` int(6) NOT NULL,
  `UserID` int(5) NOT NULL,
  `RoleID` int(3) NOT NULL,
  `Role` enum('CS','Sales') NOT NULL DEFAULT 'CS',
  PRIMARY KEY (`ID`),
  KEY `CustomerID_UserID_RoleID` (`CustomerID`,`UserID`,`RoleID`)
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_responsible`
--

LOCK TABLES `customer_responsible` WRITE;
/*!40000 ALTER TABLE `customer_responsible` DISABLE KEYS */;
INSERT INTO `customer_responsible` VALUES (98,7,'2012-02-17 06:55:48',100,2,2,'CS');
/*!40000 ALTER TABLE `customer_responsible` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_standartrates`
--

DROP TABLE IF EXISTS `customer_standartrates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_standartrates` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `ResponsibleSalesID` int(10) NOT NULL,
  `ResponsibleCSID` int(10) NOT NULL,
  `NatureOfGoods` varchar(50) NOT NULL,
  `DepartureIATA` char(3) NOT NULL,
  `DepartureLocationName` varchar(100) NOT NULL,
  `ArrivalIATA` char(3) NOT NULL,
  `ArrivalLocationName` varchar(100) NOT NULL,
  `ValidFrom` date NOT NULL,
  `ValidUntil` date NOT NULL,
  `AirlineAlphaCode` char(2) NOT NULL,
  `TransitTime` varchar(10) NOT NULL,
  `Incoterms` char(3) NOT NULL,
  `IncotermsLocation` varchar(100) NOT NULL,
  `CommentsInternal` text NOT NULL,
  `CommentsCustomer` text NOT NULL,
  `VendorID` int(11) NOT NULL,
  `SellingMinAmount` decimal(12,2) NOT NULL,
  `BuyingMinAmount` decimal(12,2) NOT NULL,
  `SellingAmountN` decimal(12,2) NOT NULL,
  `BuyingAmountN` decimal(12,2) NOT NULL,
  `SellingAmount45` decimal(12,2) NOT NULL,
  `BuyingAmount45` decimal(12,2) NOT NULL,
  `SellingAmount100` decimal(12,2) NOT NULL,
  `BuyingAmount100` decimal(12,2) NOT NULL,
  `SellingAmount200` decimal(12,2) NOT NULL,
  `BuyingAmount200` decimal(12,2) NOT NULL,
  `SellingAmount300` decimal(12,2) NOT NULL,
  `BuyingAmount300` decimal(12,2) NOT NULL,
  `SellingAmount500` decimal(12,2) NOT NULL,
  `BuyingAmount500` decimal(12,2) NOT NULL,
  `SellingAmount750` decimal(12,2) NOT NULL,
  `BuyingAmount750` decimal(12,2) NOT NULL,
  `SellingAmount1000` decimal(12,2) NOT NULL,
  `BuyingAmount1000` decimal(12,2) NOT NULL,
  `SellingAmount2000` decimal(12,2) NOT NULL,
  `BuyingAmount2000` decimal(12,2) NOT NULL,
  `SellingFSCAmount` decimal(12,2) NOT NULL,
  `BuyingFSCAmount` decimal(12,2) NOT NULL,
  `SellingSSCAmount` decimal(12,2) NOT NULL,
  `BuyingSSCAmount` decimal(12,2) NOT NULL,
  `SellingCurrencyISO` char(3) NOT NULL,
  `BuyingCurrencyISO` char(3) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB AUTO_INCREMENT=700009 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_standartrates`
--

LOCK TABLES `customer_standartrates` WRITE;
/*!40000 ALTER TABLE `customer_standartrates` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_standartrates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_standratrates_buyingsurcharges`
--

DROP TABLE IF EXISTS `customer_standratrates_buyingsurcharges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_standratrates_buyingsurcharges` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `CustomerStandartRateID` int(11) NOT NULL,
  `BaseAmount` decimal(11,2) NOT NULL,
  `MinAmount` decimal(11,2) NOT NULL,
  `MaxAmount` decimal(11,2) NOT NULL,
  `PerKgAmount` decimal(11,2) NOT NULL,
  `CurrencyISO` char(3) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerStandartRateID` (`CustomerStandartRateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_standratrates_buyingsurcharges`
--

LOCK TABLES `customer_standratrates_buyingsurcharges` WRITE;
/*!40000 ALTER TABLE `customer_standratrates_buyingsurcharges` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_standratrates_buyingsurcharges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_standratrates_sellingsurcharges`
--

DROP TABLE IF EXISTS `customer_standratrates_sellingsurcharges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_standratrates_sellingsurcharges` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `CustomerStandartRateID` int(11) NOT NULL,
  `BaseAmount` decimal(11,2) NOT NULL,
  `MinAmount` decimal(11,2) NOT NULL,
  `MaxAmount` decimal(11,2) NOT NULL,
  `PerKgAmount` decimal(11,2) NOT NULL,
  `CurrencyISO` char(3) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerStandartRateID` (`CustomerStandartRateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_standratrates_sellingsurcharges`
--

LOCK TABLES `customer_standratrates_sellingsurcharges` WRITE;
/*!40000 ALTER TABLE `customer_standratrates_sellingsurcharges` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_standratrates_sellingsurcharges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customeraccount`
--

DROP TABLE IF EXISTS `customeraccount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customeraccount` (
  `ID` int(7) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  `CompanyID` int(11) DEFAULT NULL,
  `ContractNo` varchar(50) NOT NULL,
  `InvoiceAddress` text NOT NULL,
  `RatePercent` varchar(10) NOT NULL,
  `Comments` text NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `BUIDs` varchar(100) NOT NULL,
  `ShipmentRequestZIP` varchar(20) NOT NULL,
  `ShipmentRequestStreet` varchar(200) NOT NULL,
  `ShipmentRequestCity` varchar(200) NOT NULL,
  `ShipmentRequestCountry` varchar(200) NOT NULL,
  `ShipmentRequestPhone` varchar(200) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=815 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customeraccount`
--

LOCK TABLES `customeraccount` WRITE;
/*!40000 ALTER TABLE `customeraccount` DISABLE KEYS */;
INSERT INTO `customeraccount` VALUES (100,7,'2012-03-20 12:58:59','PC LOGISTICS GROUP LLC',1,'№03/031 ОТ 20 МАРТА 2012 Г.','SOKOLOVO-MESHERSKAY STREET\nBLD.30, ROOM 48\nMOSCOW, 125466,\nRUSSIA','','',0,'|1|','','','','','');
/*!40000 ALTER TABLE `customeraccount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customeragent`
--

DROP TABLE IF EXISTS `customeragent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customeragent` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `CustomerID` int(10) NOT NULL,
  `Origin` varchar(100) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(100) NOT NULL,
  `Comments` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customeragent`
--

LOCK TABLES `customeragent` WRITE;
/*!40000 ALTER TABLE `customeragent` DISABLE KEYS */;
/*!40000 ALTER TABLE `customeragent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customerconsignee`
--

DROP TABLE IF EXISTS `customerconsignee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customerconsignee` (
  `ID` int(6) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(200) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `ShipmentRequestZIP` varchar(20) NOT NULL,
  `ShipmentRequestStreet` varchar(200) NOT NULL,
  `ShipmentRequestCity` varchar(200) NOT NULL,
  `ShipmentRequestCountry` varchar(200) NOT NULL,
  `ShipmentRequestPhone` varchar(200) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customerconsignee`
--

LOCK TABLES `customerconsignee` WRITE;
/*!40000 ALTER TABLE `customerconsignee` DISABLE KEYS */;
/*!40000 ALTER TABLE `customerconsignee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dblog`
--

DROP TABLE IF EXISTS `dblog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dblog` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `ModuleID` int(11) NOT NULL,
  `RecordID` int(11) NOT NULL DEFAULT 0,
  `FieldID` int(11) NOT NULL,
  `MasterID` int(11) NOT NULL,
  `TableID` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ModuleID_MasterID` (`ModuleID`,`MasterID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dblog`
--

LOCK TABLES `dblog` WRITE;
/*!40000 ALTER TABLE `dblog` DISABLE KEYS */;
/*!40000 ALTER TABLE `dblog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dblog_field`
--

DROP TABLE IF EXISTS `dblog_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dblog_field` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `TableID` int(10) NOT NULL,
  `FieldKey` varchar(100) NOT NULL,
  `FieldDescription` varchar(200) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `TableID_FieldKey` (`TableID`,`FieldKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dblog_field`
--

LOCK TABLES `dblog_field` WRITE;
/*!40000 ALTER TABLE `dblog_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `dblog_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dblog_module`
--

DROP TABLE IF EXISTS `dblog_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dblog_module` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `ModuleName` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ModuleName` (`ModuleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dblog_module`
--

LOCK TABLES `dblog_module` WRITE;
/*!40000 ALTER TABLE `dblog_module` DISABLE KEYS */;
/*!40000 ALTER TABLE `dblog_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dblog_table`
--

DROP TABLE IF EXISTS `dblog_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dblog_table` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `TableName` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `TableName` (`TableName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dblog_table`
--

LOCK TABLES `dblog_table` WRITE;
/*!40000 ALTER TABLE `dblog_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `dblog_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dblog_text`
--

DROP TABLE IF EXISTS `dblog_text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dblog_text` (
  `dblogID` int(11) NOT NULL,
  `Text` text DEFAULT NULL,
  `OldValue` text DEFAULT NULL,
  `NewValue` text DEFAULT NULL,
  `OldValueFormated` text DEFAULT NULL,
  `NewValueFormated` text DEFAULT NULL,
  `ObjectDescription` text DEFAULT NULL,
  PRIMARY KEY (`dblogID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dblog_text`
--

LOCK TABLES `dblog_text` WRITE;
/*!40000 ALTER TABLE `dblog_text` DISABLE KEYS */;
/*!40000 ALTER TABLE `dblog_text` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dblog_trace`
--

DROP TABLE IF EXISTS `dblog_trace`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dblog_trace` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `DbLogIDs` text DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CreatedBy` (`CreatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dblog_trace`
--

LOCK TABLES `dblog_trace` WRITE;
/*!40000 ALTER TABLE `dblog_trace` DISABLE KEYS */;
/*!40000 ALTER TABLE `dblog_trace` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deleted`
--

DROP TABLE IF EXISTS `deleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deleted` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `ModuleID` int(11) NOT NULL,
  `MasterID` int(11) NOT NULL,
  `TableID` int(11) NOT NULL,
  `RecordID` int(11) NOT NULL,
  `Restored` tinyint(1) NOT NULL,
  `TraceID` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ModuleIDMasterID` (`ModuleID`,`MasterID`),
  KEY `TableIDRecordID` (`TableID`,`RecordID`),
  KEY `TraceID` (`TraceID`),
  KEY `CreatedBy` (`CreatedBy`),
  KEY `CreatedDateTime` (`CreatedDateTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deleted`
--

LOCK TABLES `deleted` WRITE;
/*!40000 ALTER TABLE `deleted` DISABLE KEYS */;
/*!40000 ALTER TABLE `deleted` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deleted_text`
--

DROP TABLE IF EXISTS `deleted_text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deleted_text` (
  `deletedID` int(11) NOT NULL,
  `JSON` mediumtext NOT NULL,
  PRIMARY KEY (`deletedID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deleted_text`
--

LOCK TABLES `deleted_text` WRITE;
/*!40000 ALTER TABLE `deleted_text` DISABLE KEYS */;
/*!40000 ALTER TABLE `deleted_text` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deleted_trace`
--

DROP TABLE IF EXISTS `deleted_trace`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deleted_trace` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `CreatedBy` (`CreatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deleted_trace`
--

LOCK TABLES `deleted_trace` WRITE;
/*!40000 ALTER TABLE `deleted_trace` DISABLE KEYS */;
/*!40000 ALTER TABLE `deleted_trace` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_onecimport`
--

DROP TABLE IF EXISTS log_onecimport;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_onecimport` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL DEFAULT '',
  `DocDate` date NOT NULL,
  `Type` varchar(20) NOT NULL DEFAULT '',
  `CompanyID` int(11) NOT NULL,
  `SuccessID` int(11) NOT NULL,
  `Log` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyIDType` (`CompanyID`,`Type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_onecimport`
--

LOCK TABLES log_onecimport WRITE;
/*!40000 ALTER TABLE log_onecimport DISABLE KEYS */;
/*!40000 ALTER TABLE log_onecimport ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_chargedescription`
--

DROP TABLE IF EXISTS `list_chargedescription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_chargedescription` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  `NameRu` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_chargedescription`
--

LOCK TABLES `list_chargedescription` WRITE;
/*!40000 ALTER TABLE `list_chargedescription` DISABLE KEYS */;
INSERT INTO `list_chargedescription` VALUES (2,1,'2012-04-18 16:57:26','Fuel surcharge','Топливный сбор в пользу авиакомпании');
/*!40000 ALTER TABLE `list_chargedescription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_concatposition`
--

DROP TABLE IF EXISTS `list_concatposition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_concatposition` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_concatposition`
--

LOCK TABLES `list_concatposition` WRITE;
/*!40000 ALTER TABLE `list_concatposition` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_concatposition` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_consignee`
--

DROP TABLE IF EXISTS `list_consignee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_consignee` (
  `ID` int(6) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_consignee`
--

LOCK TABLES `list_consignee` WRITE;
/*!40000 ALTER TABLE `list_consignee` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_consignee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_destination`
--

DROP TABLE IF EXISTS `list_destination`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_destination` (
  `ID` int(6) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_destination`
--

LOCK TABLES `list_destination` WRITE;
/*!40000 ALTER TABLE `list_destination` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_destination` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_placeofreceipt`
--

DROP TABLE IF EXISTS `list_placeofreceipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_placeofreceipt` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_placeofreceipt`
--

LOCK TABLES `list_placeofreceipt` WRITE;
/*!40000 ALTER TABLE `list_placeofreceipt` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_placeofreceipt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_shipper`
--

DROP TABLE IF EXISTS `list_shipper`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_shipper` (
  `ID` int(6) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  `ShipmentRequestZIP` varchar(20) NOT NULL,
  `ShipmentRequestStreet` varchar(200) NOT NULL,
  `ShipmentRequestCity` varchar(200) NOT NULL,
  `ShipmentRequestCountry` varchar(200) NOT NULL,
  `ShipmentRequestPhone` varchar(200) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_shipper`
--

LOCK TABLES `list_shipper` WRITE;
/*!40000 ALTER TABLE `list_shipper` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_shipper` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `list_vessel`
--

DROP TABLE IF EXISTS `list_vessel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_vessel` (
  `ID` int(6) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `list_vessel`
--

LOCK TABLES `list_vessel` WRITE;
/*!40000 ALTER TABLE `list_vessel` DISABLE KEYS */;
/*!40000 ALTER TABLE `list_vessel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(200) NOT NULL,
  `NameRu` varchar(200) NOT NULL,
  `IATA` char(3) NOT NULL,
  `CountryCode` char(2) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `IATA` (`IATA`)
) ENGINE=InnoDB AUTO_INCREMENT=961 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location`
--

LOCK TABLES `location` WRITE;
/*!40000 ALTER TABLE `location` DISABLE KEYS */;
INSERT INTO `location` VALUES (2,0,'2012-01-10 18:46:23','Buenos Aires','','BUE','AR');
/*!40000 ALTER TABLE `location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_autolaunch`
--

DROP TABLE IF EXISTS `log_autolaunch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_autolaunch` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `ClassName` varchar(50) NOT NULL,
  `Report` mediumtext NOT NULL,
  `ShortMessage` text NOT NULL,
  `WorkTime` float NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_autolaunch`
--

LOCK TABLES `log_autolaunch` WRITE;
/*!40000 ALTER TABLE `log_autolaunch` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_autolaunch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_email`
--

DROP TABLE IF EXISTS `log_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_email` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL DEFAULT 0,
  `CreatedDateTime` datetime NOT NULL,
  `SentDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `From` varchar(100) NOT NULL,
  `To` varchar(300) NOT NULL,
  `Cc` varchar(300) NOT NULL,
  `Bcc` varchar(300) NOT NULL,
  `Subject` varchar(300) NOT NULL,
  `Body` mediumtext NOT NULL,
  `ContentType` varchar(50) NOT NULL DEFAULT 'text/html',
  `RetryCount` tinyint(4) NOT NULL DEFAULT 0,
  `ReplyTo` varchar(300) NOT NULL DEFAULT '',
  `Attachements` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_email`
--

LOCK TABLES `log_email` WRITE;
/*!40000 ALTER TABLE `log_email` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_email` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_telegram`
--

DROP TABLE IF EXISTS `log_telegram`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_telegram` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `TelegramID` int(11) NOT NULL,
  `Query` text NOT NULL,
  `Response` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CreatedBy` (`CreatedBy`),
  KEY `TelegramID` (`TelegramID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_telegram`
--

LOCK TABLES `log_telegram` WRITE;
/*!40000 ALTER TABLE `log_telegram` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_telegram` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metric`
--

DROP TABLE IF EXISTS `metric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metric` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `ActionType` varchar(150) NOT NULL,
  `TimeMs` int(11) NOT NULL DEFAULT 0,
  `Payload` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ActionType` (`ActionType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metric`
--

LOCK TABLES `metric` WRITE;
/*!40000 ALTER TABLE `metric` DISABLE KEYS */;
/*!40000 ALTER TABLE `metric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration`
--

DROP TABLE IF EXISTS `migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migration` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  `Report` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration`
--

LOCK TABLES `migration` WRITE;
/*!40000 ALTER TABLE `migration` DISABLE KEYS */;
/*!40000 ALTER TABLE `migration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulesession`
--

DROP TABLE IF EXISTS `modulesession`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulesession` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `IDs` text NOT NULL,
  `StartPage` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulesession`
--

LOCK TABLES `modulesession` WRITE;
/*!40000 ALTER TABLE `modulesession` DISABLE KEYS */;
/*!40000 ALTER TABLE `modulesession` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oceancarrier`
--

DROP TABLE IF EXISTS `oceancarrier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oceancarrier` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oceancarrier`
--

LOCK TABLES `oceancarrier` WRITE;
/*!40000 ALTER TABLE `oceancarrier` DISABLE KEYS */;
INSERT INTO `oceancarrier` VALUES (1,12,'2016-12-09 05:34:26','HAPAG-LLOYD (CANADA) INC');
/*!40000 ALTER TABLE `oceancarrier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ourcompany`
--

DROP TABLE IF EXISTS `ourcompany`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ourcompany` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `Type` enum('offshore','onshore') NOT NULL,
  `Address` text NOT NULL,
  `BeneficiaryBank` text NOT NULL,
  `CorrespondentBank` text NOT NULL,
  `OrderID` tinyint(2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `Company` (`Name`,`Type`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ourcompany`
--

LOCK TABLES `ourcompany` WRITE;
/*!40000 ALTER TABLE `ourcompany` DISABLE KEYS */;
INSERT INTO `ourcompany` VALUES (1,0,'2017-07-18 13:33:10','Proactive Logistic Solutions Ltd. (EUR)','offshore','Stasinou, 1, \nMitsi Building 1, 1st Fl., Off. 4, \nPlateia Eleftherias, P.C. 1060, Nicosia, Cyprus','Account number (IBAN): EE311200001233976256\nCitadele banka Eesti filiaal, \nRoosikrantsi 2, Tallinn, Estonia\nBIC (S.W.I.F.T.): PARXEE22','Correspondent bank´s BIC (S.W.I.F.T.): PARXLV22',1),(2,0,'2012-11-21 13:54:28','Global Transport Services Inc.','offshore','P.O. 556, Main Street, Charlestown, Nevis','Account number: 333448890002\nIBAN: EE503300333448890002\nBank: DANSKE BANK A/S ESTONIA BRANCH	\n15015 Tallinn, Estonia\nSWIFT: FORE EE 2X','(for payments in USD)\nDeutsche Bank Trust Company Americas,\nNew York, USA SWIFT: BKTR US 33\nor\nJPMorgan Chase Bank,\nNew York, USA SWIFT: CHAS US 33',2),(4,1,'2017-02-01 15:16:39','PLS OOO','onshore','125167, Russia, Moscow, Planetnaya str., d.29, pom. I, room. 1\n\nINN 7715694328 / KPP 771401001','КБ «МИА» (АО)\nAccount: 40702810200000000426\nBIC: 044525231','Corr Account: 30101810545250000231',3),(5,6,'2017-02-01 15:16:56','PGL ООО','onshore','Planetnaya str. , d.29 , pom. I, room . 2\nMoscow, 125167,\nRussia\n\nINN 771401001/ KPP 771601001','КБ «МИА» (АО)\nAccount: 40702810500000000427\nBIC: 044525231','Corr Account: 30101810545250000231',4),(7,14,'2017-02-01 15:44:18','Avia Cargo Broker LLC','offshore','','','',10),(8,9,'2017-02-01 15:43:08','Airfreight Trading LLC','offshore','','','',7),(9,12,'2017-02-01 15:43:17','AVIACARGOSERVICE LLC','offshore','','','',8),(10,12,'2017-02-01 15:43:26','AVIONIKA CARGO LLC','offshore','','','',9),(11,9,'2017-08-14 10:38:09','PGL OOO new','offshore','125057, Russia, Moscow, Leningradsky Prospect, \nBldg 57, Ground floor, Office II, Room 13\n\nINN 7716829539 / KPP 771401001','Bank for Innovation and Development\nAccount: 40702810300000005872\nBIC: 044525104','Corr Account: 30101810245250000104',6),(12,9,'2017-08-14 10:38:26','PLS OOO new','offshore','125057, Russia, Moscow, Leningradsky Prospect, \nBldg 57, Ground floor, Office II, Room 13\n\nINN 7716829521 / KPP 771401001','Bank for Innovation and Development\nAccount: 40702810000000005871\nBIC: 044525104','Corr Account: 30101810245250000104',5),(13,9,'2017-07-24 21:55:32','Proactive Logistic Solutions Ltd. (USD)','offshore','Stasinou, 1, \nMitsi Building 1, 1st Fl., Off. 4, \nPlateia Eleftherias, P.C. 1060, Nicosia, Cyprus','PPF BANKA A.S.,\n160 41, Prague 6, Czech Republic,\nPPF GATE, Evropska, 2690/17 P.O.: BOX 177\nIBAN CZ55 6000 0000 0060 5478 0003\nSWIFT: PMBP CZ PP','Correspondent bank´s BIC (S.W.I.F.T.): IRVTUS3N,  \nThe Bank of New York, New York, NY,\nOne Wall Street\nAccount: 890-0500-689',0);
/*!40000 ALTER TABLE `ourcompany` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ourcompanybank`
--

DROP TABLE IF EXISTS `ourcompanybank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ourcompanybank` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `CompanyID` int(11) NOT NULL,
  `Name` varchar(200) NOT NULL,
  `BeneficiaryBank` text NOT NULL,
  `CorrespondentBank` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ourcompanybank`
--

LOCK TABLES `ourcompanybank` WRITE;
/*!40000 ALTER TABLE `ourcompanybank` DISABLE KEYS */;
/*!40000 ALTER TABLE `ourcompanybank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pdfhash`
--

DROP TABLE IF EXISTS `pdfhash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pdfhash` (
  `Filepath` varchar(255) NOT NULL,
  `HtmlMD5` char(32) NOT NULL,
  PRIMARY KEY (`Filepath`),
  KEY `Filepath_HtmlMD5` (`Filepath`,`HtmlMD5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pdfhash`
--

LOCK TABLES `pdfhash` WRITE;
/*!40000 ALTER TABLE `pdfhash` DISABLE KEYS */;
/*!40000 ALTER TABLE `pdfhash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phpsession`
--

DROP TABLE IF EXISTS `phpsession`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phpsession` (
  `SessionID` char(32) NOT NULL DEFAULT '',
  `CreatedTime` int(11) DEFAULT NULL,
  `Data` text DEFAULT NULL,
  PRIMARY KEY (`SessionID`),
  KEY `CreatedTime` (`CreatedTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phpsession`
--

LOCK TABLES `phpsession` WRITE;
/*!40000 ALTER TABLE `phpsession` DISABLE KEYS */;
/*!40000 ALTER TABLE `phpsession` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phpsessionflash`
--

DROP TABLE IF EXISTS `phpsessionflash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phpsessionflash` (
  `SessionID` char(32) NOT NULL,
  `Messages` mediumtext NOT NULL,
  PRIMARY KEY (`SessionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phpsessionflash`
--

LOCK TABLES `phpsessionflash` WRITE;
/*!40000 ALTER TABLE `phpsessionflash` DISABLE KEYS */;
/*!40000 ALTER TABLE `phpsessionflash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `port`
--

DROP TABLE IF EXISTS `port`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `port` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(200) NOT NULL,
  `NameRu` varchar(200) NOT NULL,
  `isPOL` tinyint(4) NOT NULL,
  `isPOD` tinyint(4) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `port`
--

LOCK TABLES `port` WRITE;
/*!40000 ALTER TABLE `port` DISABLE KEYS */;
INSERT INTO `port` VALUES (36,0,'2014-12-19 13:46:30','ATLANTA, GEORGIA, USA','',1,0);
/*!40000 ALTER TABLE `port` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portterminal`
--

DROP TABLE IF EXISTS `portterminal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portterminal` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL,
  `Synonyms` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portterminal`
--

LOCK TABLES `portterminal` WRITE;
/*!40000 ALTER TABLE `portterminal` DISABLE KEYS */;
/*!40000 ALTER TABLE `portterminal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `queue_email`
--

DROP TABLE IF EXISTS `queue_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `queue_email` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `PID` char(13) DEFAULT NULL,
  `From` varchar(100) NOT NULL,
  `To` varchar(300) NOT NULL,
  `Cc` varchar(300) NOT NULL,
  `Bcc` varchar(300) NOT NULL,
  `Subject` varchar(300) NOT NULL,
  `Body` mediumtext NOT NULL,
  `ContentType` varchar(50) NOT NULL DEFAULT 'text/html',
  `RetryCount` tinyint(4) NOT NULL DEFAULT 0,
  `ReplyTo` varchar(200) NOT NULL,
  `Attachements` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `PID_RetryCount` (`PID`,`RetryCount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `queue_email`
--

LOCK TABLES `queue_email` WRITE;
/*!40000 ALTER TABLE `queue_email` DISABLE KEYS */;
/*!40000 ALTER TABLE `queue_email` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation`
--

DROP TABLE IF EXISTS `quotation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `CompanyID` int(11) NOT NULL,
  `CustomerID` int(10) NOT NULL,
  `VendorID` int(10) NOT NULL,
  `ResponsibleSalesID` int(10) NOT NULL,
  `ResponsibleCSID` int(10) NOT NULL,
  `NatureOfGoods` varchar(50) NOT NULL,
  `DepartureIATA` char(3) NOT NULL,
  `DepartureLocationName` varchar(100) NOT NULL,
  `ArrivalIATA` char(3) NOT NULL,
  `ArrivalLocationName` varchar(100) NOT NULL,
  `ValidFrom` date NOT NULL,
  `ValidUntil` date NOT NULL,
  `CurrencyISO` char(3) NOT NULL,
  `GrossWeight` decimal(10,3) NOT NULL,
  `ChargeableWeight` decimal(10,3) NOT NULL,
  `AirlineAlphaCode` char(2) NOT NULL,
  `TransitTime` varchar(10) NOT NULL,
  `Incoterms` char(3) NOT NULL,
  `IncotermsLocation` varchar(100) NOT NULL,
  `CommentsInternal` text NOT NULL,
  `CommentsCustomer` text NOT NULL,
  `Status` enum('draft','pending','win','lost','merged') NOT NULL,
  `MergedQuotationID` int(11) NOT NULL,
  `CargoUOM` enum('cm','inch') NOT NULL DEFAULT 'cm',
  `CacheGP` decimal(9,2) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB AUTO_INCREMENT=8697 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation`
--

LOCK TABLES `quotation` WRITE;
/*!40000 ALTER TABLE `quotation` DISABLE KEYS */;
INSERT INTO `quotation` VALUES (100,8,'2013-09-27 05:46:19','270913/MIASVO/Q1000/RU',1,93,0,8,0,'LAMPS','MIA','MIAMI INT\'L','SVO','SHEREMETYEVO','2013-09-27','2013-10-27','USD',180.000,1317.000,'RU','5-7','EXW','5772 MIAMI LAKES DRIVE EAST MIAMI LAKES, FL, USA','','MAIN DECK CARGO','lost',0,'cm',614.48);
/*!40000 ALTER TABLE `quotation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotationcargo`
--

DROP TABLE IF EXISTS `quotationcargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotationcargo` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `QuotationID` int(11) NOT NULL,
  `Width` int(11) NOT NULL,
  `Height` int(11) NOT NULL,
  `Length` int(11) NOT NULL,
  `Pieces` int(11) NOT NULL,
  `Volume` decimal(10,3) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuotationID` (`QuotationID`)
) ENGINE=InnoDB AUTO_INCREMENT=11751 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotationcargo`
--

LOCK TABLES `quotationcargo` WRITE;
/*!40000 ALTER TABLE `quotationcargo` DISABLE KEYS */;
INSERT INTO `quotationcargo` VALUES (89,8,'2013-09-27 05:46:20',100,0,0,0,0,7.900);
/*!40000 ALTER TABLE `quotationcargo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotationexpenses`
--

DROP TABLE IF EXISTS `quotationexpenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotationexpenses` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `QuotationID` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Per` enum('GW','CW','MAWB','Piece') NOT NULL,
  `MinAmount` decimal(10,2) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuotationID` (`QuotationID`)
) ENGINE=InnoDB AUTO_INCREMENT=47508 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotationexpenses`
--

LOCK TABLES `quotationexpenses` WRITE;
/*!40000 ALTER TABLE `quotationexpenses` DISABLE KEYS */;
INSERT INTO `quotationexpenses` VALUES (367,8,'2013-09-27 05:46:22',100,'Airfreight',2.55,'CW',0.00,3358.35),(368,8,'2013-09-27 05:46:22',100,'Fuel surcharge',1.10,'GW',0.00,198.00),(369,8,'2013-09-27 05:46:23',100,'Security Surcharge',0.15,'GW',0.00,27.00),(370,8,'2013-09-27 05:46:23',100,'Pick Up',1.00,'MAWB',0.00,255.00),(371,8,'2013-09-27 05:46:23',100,'Transfer charges',85.00,'MAWB',0.00,85.00),(372,8,'2013-09-27 05:46:23',100,'Export Customs Clearance',45.00,'MAWB',0.00,45.00),(373,8,'2013-09-27 05:46:23',100,'EU Fee',15.00,'MAWB',0.00,15.00),(374,8,'2013-09-27 05:46:24',100,'AWB Fee',10.00,'MAWB',0.00,10.00),(375,8,'2013-09-27 05:46:24',100,'Courier Fee',25.00,'MAWB',0.00,25.00);
/*!40000 ALTER TABLE `quotationexpenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotationincome`
--

DROP TABLE IF EXISTS `quotationincome`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotationincome` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `QuotationID` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Per` enum('GW','CW','MAWB','Piece') NOT NULL,
  `MinAmount` decimal(10,2) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuotationID` (`QuotationID`)
) ENGINE=InnoDB AUTO_INCREMENT=35474 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotationincome`
--

LOCK TABLES `quotationincome` WRITE;
/*!40000 ALTER TABLE `quotationincome` DISABLE KEYS */;
INSERT INTO `quotationincome` VALUES (407,8,'2013-09-27 05:46:20',100,'Airfreight',2.99,'CW',0.00,3937.83),(408,8,'2013-09-27 05:46:20',100,'Fuel surcharge',1.10,'GW',0.00,198.00),(409,8,'2013-09-27 05:46:21',100,'Security Surcharge',0.15,'GW',0.00,27.00),(410,8,'2013-09-27 05:46:21',100,'Pick Up',1.00,'MAWB',0.00,275.00),(411,8,'2013-09-27 05:46:21',100,'Transfer charges',1.00,'MAWB',0.00,85.00),(412,8,'2013-09-27 05:46:21',100,'EU Fee',15.00,'MAWB',0.00,15.00),(413,8,'2013-09-27 05:46:22',100,'Export Customs Clearance',45.00,'MAWB',0.00,45.00),(414,8,'2013-09-27 05:46:22',100,'AWB Fee',15.00,'MAWB',0.00,15.00),(415,8,'2013-09-27 05:46:22',100,'Courier Fee',35.00,'MAWB',0.00,35.00);
/*!40000 ALTER TABLE `quotationincome` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reportaccess`
--

DROP TABLE IF EXISTS `reportaccess`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reportaccess` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `ReportClass` varchar(100) NOT NULL,
  `UserIDs` varchar(100) NOT NULL,
  `GroupIDs` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ReportClass` (`ReportClass`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reportaccess`
--

LOCK TABLES `reportaccess` WRITE;
/*!40000 ALTER TABLE `reportaccess` DISABLE KEYS */;
/*!40000 ALTER TABLE `reportaccess` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `responsiblerole`
--

DROP TABLE IF EXISTS `responsiblerole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `responsiblerole` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `Type` enum('','DP','CS','Sales') NOT NULL,
  `OrderID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `responsiblerole`
--

LOCK TABLES `responsiblerole` WRITE;
/*!40000 ALTER TABLE `responsiblerole` DISABLE KEYS */;
INSERT INTO `responsiblerole` VALUES (2,0,'2013-04-26 08:14:19','Customer Service','CS',2),(3,0,'2013-04-26 08:14:21','Sales','Sales',3),(4,9,'2016-06-06 14:07:16','Accounting','DP',4);
/*!40000 ALTER TABLE `responsiblerole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipment`
--

DROP TABLE IF EXISTS `shipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipment` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` bigint(20) NOT NULL,
  `TrafficID` int(5) NOT NULL,
  `CompanyID` int(5) NOT NULL,
  `CustomerID` int(6) NOT NULL,
  `CustomerAccountID` int(7) NOT NULL,
  `ConsigneeID` int(7) NOT NULL,
  `Consignee` varchar(100) NOT NULL COMMENT 'Consignee (old)',
  `ConsigneeReference` varchar(100) NOT NULL DEFAULT '',
  `MAWBNumber` varchar(100) NOT NULL,
  `MAWBDate` date NOT NULL,
  `FlightNo` varchar(50) NOT NULL,
  `FlightDate` date NOT NULL,
  `Pieces` int(11) NOT NULL,
  `ChargeableWeight` decimal(9,2) NOT NULL,
  `GrossWeight` decimal(9,2) NOT NULL,
  `Volume` decimal(9,3) NOT NULL,
  `NatureOfGoods` varchar(200) NOT NULL,
  `Incoterms_old` char(3) NOT NULL,
  `Incoterms` char(3) NOT NULL,
  `IncotermsLocation` varchar(100) NOT NULL,
  `AirlineAlphaCode` char(2) NOT NULL,
  `OceanCarrierID` int(11) NOT NULL,
  `DepartureIATA` char(3) NOT NULL,
  `DepartureLocationName` varchar(100) NOT NULL,
  `POLID` int(11) NOT NULL,
  `ArrivalIATA` char(3) NOT NULL,
  `ArrivalLocationName` varchar(100) NOT NULL,
  `PODID` int(11) NOT NULL,
  `PlaceOfReceipt` varchar(100) NOT NULL,
  `PODTerminalID` int(7) NOT NULL,
  `PortExpeditorID` int(7) NOT NULL,
  `VendorID` int(7) NOT NULL,
  `TruckerID` int(7) NOT NULL,
  `Destination` varchar(100) NOT NULL,
  `VesselID` int(6) NOT NULL,
  `Vessel` varchar(100) NOT NULL,
  `VoyageNbr` varchar(50) NOT NULL,
  `TruckNumber` varchar(100) NOT NULL,
  `ContainerName` varchar(100) NOT NULL,
  `ContainerType` tinyint(1) NOT NULL,
  `Month` date NOT NULL,
  `RequestDate` date NOT NULL,
  `ReadyForCarriageDate` date NOT NULL,
  `ETD` date NOT NULL,
  `ATD` date NOT NULL,
  `ETA` date NOT NULL,
  `ATA` date NOT NULL,
  `PUPDate` date NOT NULL,
  `ERTDate` date NOT NULL,
  `FileClosed` tinyint(1) NOT NULL,
  `Color` tinyint(4) NOT NULL,
  `EmailNoticeSent` tinyint(4) NOT NULL,
  `Comments` text NOT NULL,
  `ResponsibleCS` int(5) NOT NULL,
  `ResponsibleDP` int(5) NOT NULL,
  `ResponsibleSales` int(5) NOT NULL,
  `BUIDs` varchar(10) NOT NULL,
  `InsuranceAmount` decimal(12,2) NOT NULL,
  `InsuranceCurrencyISO` char(3) NOT NULL,
  `QuotationID` int(11) NOT NULL,
  `ShipmentRequestNo` int(11) NOT NULL,
  `CacheShippersName` text DEFAULT NULL,
  `CacheShippersReference` text DEFAULT NULL,
  `CacheShippersHAWBNumber` text DEFAULT NULL,
  `CacheShippersHAWBDate` text DEFAULT NULL,
  `CacheCSIncomeinUSD` decimal(9,2) NOT NULL COMMENT 'CS Income (USD)',
  `CacheCSExpensesinUSD` decimal(9,2) NOT NULL COMMENT 'CS Expenses (USD)',
  `CacheEGPinUSD` decimal(9,2) NOT NULL COMMENT 'EGP (USD)',
  `CacheGPinUSD` decimal(9,2) NOT NULL COMMENT 'GP (USD)',
  `CacheCSCount` int(3) NOT NULL,
  `CacheIncomeinUSD` decimal(9,2) NOT NULL,
  `CacheExpensesinUSD` decimal(9,2) NOT NULL,
  `CacheGPDeviationinUSD` decimal(9,2) NOT NULL,
  `CacheCriteriaText` text NOT NULL,
  `CacheUsedFileNumbersJSON` text NOT NULL,
  `DocsSentToAccountingDate` date NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `FileNumber` (`Name`),
  KEY `CustomerAccountID` (`CustomerAccountID`),
  KEY `PODTerminalID` (`PODTerminalID`),
  KEY `VesselID` (`VesselID`),
  KEY `ResponsibleCS` (`ResponsibleCS`),
  KEY `HtmlColor` (`Color`),
  KEY `CustomerID` (`CustomerID`),
  KEY `ResponsibleSales` (`ResponsibleSales`),
  KEY `Month` (`Month`),
  KEY `ResponsibleDP` (`ResponsibleDP`),
  KEY `DepartureIATA` (`DepartureIATA`),
  KEY `ArrivalIATA` (`ArrivalIATA`),
  KEY `AirlineAlphaCode` (`AirlineAlphaCode`)
) ENGINE=InnoDB AUTO_INCREMENT=12555 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipment`
--

LOCK TABLES `shipment` WRITE;
/*!40000 ALTER TABLE `shipment` DISABLE KEYS */;
INSERT INTO `shipment` VALUES (7773,12,'2016-02-20 10:09:55',105967,1,4,330,367,0,'DIAKON','Temp.control +2+8 c','580-09255411','2016-02-24','','0000-00-00',2,397.00,397.00,2.266,'CONSUMABLES FOR ANALYSERS','','FCA','FREITAL, GERMANY','RU',0,'FRA','FRANKFURT INT\'L',0,'SVO','SHEREMETYEVO',0,'FREITAL, GERMANY',0,0,27,0,'SVO 2',0,'','','','',0,'2016-02-01','0000-00-00','2016-02-24','2016-02-27','2016-02-27','2016-02-27','2016-02-27','0000-00-00','0000-00-00',1,0,0,'',12,0,10,'|1|',0.00,'',0,0,'DR. MULLER GERATEBAU GMBH','','','0000-00-00',1613.19,1373.85,239.34,524.16,1,1613.19,1089.03,284.82,'','{\"1\":\"105967\"}','0000-00-00'),(8458,14,'2016-07-20 14:25:18',106525,1,5,256,227,0,'VIZAAR RUSSIA','','125-26001743','2016-07-20','','0000-00-00',3,64.00,46.20,0.382,'VIDEO ENDOSCOPE','','EXW','','BA',0,'FRA','FRANKFURT INT\'L',0,'LED','PULKOVO',0,'FRANKFURT',0,0,27,0,'ST. PETERSBURG',0,'','','','',0,'2016-07-01','0000-00-00','2016-07-18','2016-07-20','2016-07-20','2016-07-21','2016-07-21','0000-00-00','0000-00-00',1,0,0,'',14,0,14,'|2|',0.00,'',0,0,NULL,NULL,NULL,NULL,0.00,0.00,0.00,69.58,0,438.62,369.04,69.58,'','{\"1\":\"106525\"}','0000-00-00');
/*!40000 ALTER TABLE `shipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipmentcriterion`
--

DROP TABLE IF EXISTS `shipmentcriterion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipmentcriterion` (
  `ShipmentID` int(11) NOT NULL,
  `CriterionID` int(5) NOT NULL,
  `Message` text NOT NULL,
  PRIMARY KEY (`ShipmentID`,`CriterionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipmentcriterion`
--

LOCK TABLES `shipmentcriterion` WRITE;
/*!40000 ALTER TABLE `shipmentcriterion` DISABLE KEYS */;
/*!40000 ALTER TABLE `shipmentcriterion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipmentshipper`
--

DROP TABLE IF EXISTS `shipmentshipper`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipmentshipper` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  `ShipmentID` int(10) NOT NULL,
  `Reference` varchar(100) NOT NULL,
  `HAWBNumber` varchar(100) NOT NULL,
  `HAWBDate` date NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ShipmentID` (`ShipmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipmentshipper`
--

LOCK TABLES `shipmentshipper` WRITE;
/*!40000 ALTER TABLE `shipmentshipper` DISABLE KEYS */;
/*!40000 ALTER TABLE `shipmentshipper` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_param`
--

DROP TABLE IF EXISTS `system_param`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_param` (
  `ID` int(7) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Name` varchar(255) NOT NULL,
  `Value` text NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=7383 DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_param`
--

LOCK TABLES `system_param` WRITE;
/*!40000 ALTER TABLE `system_param` DISABLE KEYS */;
INSERT INTO `system_param` VALUES (7319,0,'2011-09-24 20:28:35','Company','PLS'),(7320,0,'2011-04-26 09:40:15','DefaultCurrencyProvider','CBRF'),(7321,0,'2012-01-10 18:46:10','excel_export_format','2007'),(7322,0,'2012-02-04 06:15:18','ArchiveDatabase','book2_a_archive'),(7323,0,'2012-02-29 06:28:18','track-trace','pls'),(7370,8,'2013-04-04 19:37:50','EmailNoticeSubject','Shipment notice: AWB {MAWBNumber}/{Pieces}/{GrossWeight}'),(7371,8,'2013-04-04 19:37:50','EmailNoticeBody','Dear {ContactPersonName},\r\n\r\nWe are pleased to inform that your shipment has been booked as following:\r\n \r\nAirwaybill: {MAWBNumber}\r\nNumber of pieces: {Pieces}\r\nGross weight in kgs: {GrossWeight}\r\nChargeable weight in kgs: {ChargeableWeight}\r\nVolume in cb.m.: {Volume}\r\n\r\nRoute: {DepartureIATA} - {ArrivalIATA}\r\n\r\nYou can track your shipment status on: {TrackTraceLink}\r\n\r\nIn case of any question or inquiries on above please contact Your Sales or Customer service Manager.\r\n\r\nPlease do not reply to this automatic message. \r\nThank you for your business! \r\nSincerly Yours, \r\nProactive Global Logistics Group \r\nDISCLAMER: \r\nPlease be aware that web site http://www.track-trace.com contains links to other sites and has to be considered for infomation purposes only. Proactive Global Logistics group can not be held responsible for accuracy of information and status changes provided under the link above.\r\nPlease note that we are not responsible for the privacy practices of such other sites. We encourage our users to be aware when they leave this site and to read the privacy statements of each and every web site that collects personally identifiable information. This privacy statement applies solely to information collected by this web site.'),(7374,8,'2014-04-23 10:40:26','ColorHints','{\"ColorHint1\":\"\\u0421\\u0438\\u0433\\u043d\\u0430\\u043b\\u044c\\u043d\\u044b\\u0439 \\u0446\\u0432\\u0435\\u0442, \\u043a\\u043e\\u0433\\u0434\\u0430 Sales \\u0441\\u0438\\u0433\\u043d\\u0430\\u043b\\u0438\\u0437\\u0438\\u0440\\u0443\\u044e\\u0442 \\u043e \\u043d\\u0435\\u043f\\u043e\\u0440\\u044f\\u0434\\u043a\\u0435 \\u0441 \\u0444\\u0430\\u0439\\u043b\\u043e\\u043c \\u0432 Ops (Ops \\u043d\\u0435 \\u0438\\u0441\\u043f\\u043e\\u043b\\u044c\\u0437\\u0443\\u0435\\u0442\\u0441\\u044f)\",\"ColorHint2\":\"\\u0415\\u0441\\u0442\\u044c \\u0432\\u0445\\u043e\\u0434\\u044f\\u0449\\u0438\\u0439 \\u0441\\u0447\\u0435\\u0442 \\u043e\\u0442 Vendor, \\u043d\\u043e \\u043d\\u0435 \\u0432\\u044b\\u0441\\u0442\\u0430\\u0432\\u043b\\u0435\\u043d \\u0438\\u0441\\u0445\\u043e\\u0434\\u044f\\u0449\\u0438\\u0439 \\u043d\\u0430 Customer\",\"ColorHint3\":\"\\u0415\\u0441\\u0442\\u044c \\u0438\\u0441\\u0445\\u043e\\u0434\\u044f\\u0449\\u0438\\u0439 \\u043d\\u0430 Customer, \\u043d\\u043e \\u043d\\u0435\\u0442 \\u0432\\u0445\\u043e\\u0434\\u044f\\u0449\\u0438\\u0445 \\/ -\\u0435\\u0433\\u043e \\u043e\\u0442 Vendor\",\"ColorHint4\":\"\\u0421\\u043b\\u0443\\u0447\\u0430\\u0438, \\u043a\\u043e\\u0433\\u0434\\u0430 \\u0433\\u0440\\u0443\\u0437 \\u043d\\u0435 \\u043b\\u0435\\u0442\\u0438\\u0442 \\u0438 \\u0444\\u0430\\u0439\\u043b \\u043d\\u0435\\u043e\\u0431\\u0445\\u043e\\u0434\\u0438\\u043c\\u043e \\u043e\\u0442\\u0432\\u0435\\u0442\\u0441\\u0442\\u0432\\u0435\\u043d\\u043d\\u043e\\u043c\\u0443 Sales \\u0443\\u0434\\u0430\\u043b\\u0438\\u0442\\u044c\",\"ColorHint5\":\"\\u0424\\u0430\\u0439\\u043b \\u043f\\u043e\\u0434\\u0433\\u043e\\u0442\\u043e\\u0432\\u043b\\u0435\\u043d \\u043a \\u0437\\u0430\\u043a\\u0440\\u044b\\u0442\\u0438\\u044e, \\u043d\\u043e \\u043d\\u0435 \\u043f\\u0440\\u043e\\u0441\\u0442\\u0430\\u0432\\u043b\\u0435\\u043d\\u043e ATA\",\"ColorHint6\":\"\\u0424\\u0430\\u0439\\u043b \\u043f\\u043e\\u0434\\u0433\\u043e\\u0442\\u043e\\u0432\\u043b\\u0435\\u043d \\u043a \\u0437\\u0430\\u043a\\u0440\\u044b\\u0442\\u0438\\u044e (\\u0435\\u0441\\u0442\\u044c \\u043e\\u0441\\u043e\\u0431\\u0435\\u043d\\u043d\\u043e\\u0441\\u0442\\u0438 \\u043f\\u0440\\u0438 \\u0437\\u0430\\u043a\\u0440\\u044b\\u0442\\u0438\\u0438)\",\"ColorHint7\":\"\\u041d\\u0435 \\u0438\\u0441\\u043f\\u043e\\u043b\\u044c\\u0437\\u0443\\u0435\\u0442\\u0441\\u044f\",\"ColorHint8\":\"\\u0421\\u0438\\u0433\\u043d\\u0430\\u043b\\u044c\\u043d\\u044b\\u0439 \\u0446\\u0432\\u0435\\u0442, \\u043a\\u043e\\u0433\\u0434\\u0430 Ops \\u0441\\u0438\\u0433\\u043d\\u0430\\u043b\\u0438\\u0437\\u0438\\u0440\\u0443\\u0435\\u0442 Sales \\u043e \\u043d\\u0435\\u043f\\u043e\\u0440\\u044f\\u0434\\u043a\\u0435 \\u0441 \\u0444\\u0430\\u0439\\u043b\\u043e\\u043c (Sales \\u043d\\u0435 \\u0438\\u0441\\u043f\\u043e\\u043b\\u044c\\u0437\\u0443\\u0435\\u0442\\u0441\\u044f)\",\"ColorHint9\":\"\\u041d\\u0435 \\u0438\\u0441\\u043f\\u043e\\u043b\\u044c\\u0437\\u0443\\u0435\\u0442\\u0441\\u044f\",\"ColorHint10\":\"\\u041d\\u0435 \\u0438\\u0441\\u043f\\u043e\\u043b\\u044c\\u0437\\u0443\\u0435\\u0442\\u0441\\u044f\",\"ColorHint11\":\"\\u041d\\u0435 \\u0438\\u0441\\u043f\\u043e\\u043b\\u044c\\u0437\\u0443\\u0435\\u0442\\u0441\\u044f\"}'),(7376,8,'2014-11-27 13:43:01','DefaultAddons','{\"AddonUSDMinAmount\":150,\"AddonUSDAmountN\":0,\"AddonUSDAmount45\":3,\"AddonUSDAmount100\":2,\"AddonUSDAmount200\":1.5,\"AddonUSDAmount300\":1,\"AddonUSDAmount500\":0.85,\"AddonUSDAmount750\":0.7,\"AddonUSDAmount1000\":0.6,\"AddonUSDAmount2000\":0.55,\"AddonEURMinAmount\":120,\"AddonEURAmountN\":0,\"AddonEURAmount45\":2.4,\"AddonEURAmount100\":1.6,\"AddonEURAmount200\":1.2,\"AddonEURAmount300\":0.75,\"AddonEURAmount500\":0.65,\"AddonEURAmount750\":0.5,\"AddonEURAmount1000\":0.45,\"AddonEURAmount2000\":0.4}'),(7382,9,'2017-10-20 13:18:07','blocked','0');
/*!40000 ALTER TABLE `system_param` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traffic`
--

DROP TABLE IF EXISTS `traffic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traffic` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `FileNumberFrom` bigint(20) unsigned NOT NULL,
  `FileNumberUntil` bigint(20) unsigned NOT NULL,
  `NextFileNumber` bigint(20) unsigned NOT NULL,
  `OrderID` int(10) unsigned NOT NULL,
  `Type` enum('avia','ocean','truck') NOT NULL DEFAULT 'avia',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traffic`
--

LOCK TABLES `traffic` WRITE;
/*!40000 ALTER TABLE `traffic` DISABLE KEYS */;
INSERT INTO `traffic` VALUES (1,0,'0000-00-00 00:00:00','Air - Import',100000,199999,109792,1,'avia'),(2,0,'0000-00-00 00:00:00','Air - Export',200000,299000,201446,2,'avia'),(3,0,'0000-00-00 00:00:00','Air - Local',300000,399000,300089,3,'avia'),(6,1,'2011-08-10 13:08:02','Air - Transit',400000,499999,401163,4,'avia'),(7,1,'2011-08-10 13:08:18','Ocean - Import/Export',500000,599999,500182,5,'ocean'),(8,1,'2011-08-10 13:08:34','Truck - Import/Export',600000,699999,600174,6,'truck'),(9,1,'2011-08-10 13:08:55','Multimodal',700000,799999,700044,7,''),(10,1,'2011-08-10 13:09:09','Others / Misc',800000,899999,800082,8,'');
/*!40000 ALTER TABLE `traffic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uom`
--

DROP TABLE IF EXISTS `uom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uom` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `Descriptions` text NOT NULL,
  `OrderID` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `OrderID` (`OrderID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uom`
--

LOCK TABLES `uom` WRITE;
/*!40000 ALTER TABLE `uom` DISABLE KEYS */;
INSERT INTO `uom` VALUES (1,0,'2012-01-10 18:46:09','Kg','Airfreight',1),(2,1,'2012-01-10 18:49:12','Freight','',2),(3,1,'2012-01-10 18:49:25','%','Insurance',3);
/*!40000 ALTER TABLE `uom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usage`
--

DROP TABLE IF EXISTS `usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usage` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTim` timestamp NOT NULL DEFAULT current_timestamp(),
  `Module` varchar(50) NOT NULL,
  `Action` varchar(100) NOT NULL,
  `Payload` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Module_Action` (`Module`,`Action`)
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usage`
--

LOCK TABLES `usage` WRITE;
/*!40000 ALTER TABLE `usage` DISABLE KEYS */;
INSERT INTO `usage` VALUES (1,10,'2016-09-21 12:19:29','Shipment','ModuleAbstract:ExportToExcel','{\"count\":7,\"filterValues\":{\"FileNumbers\":\"200787-200793\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(2,10,'2016-09-22 08:26:41','Shipment','ModuleAbstract:ExportToExcel','{\"count\":287,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_8\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-01-01\",\"MonthUntil\":\"2016-04-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(3,10,'2016-09-22 08:27:25','Shipment','ModuleAbstract:ExportToExcel','{\"count\":164,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_9\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-01-01\",\"MonthUntil\":\"2016-04-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(4,10,'2016-09-22 08:27:34','Shipment','ModuleAbstract:ExportToExcel','{\"count\":164,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_9\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-01-01\",\"MonthUntil\":\"2016-04-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(5,9,'2016-09-22 13:58:38','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_8\",\"BUIDsFilter\":\"1\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-04-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(6,14,'2016-09-23 13:17:06','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":1,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"230\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"DocDateFrom\":\"2013-01-01\",\"DocDateUntil\":\"2016-09-08\"}}'),(7,14,'2016-09-23 13:18:35','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":5,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"230\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"21\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"DocDateFrom\":\"2013-01-01\",\"DocDateUntil\":\"2016-09-08\"}}'),(8,15,'2016-09-27 09:19:16','Shipment','ModuleAbstract:ExportToExcel','{\"count\":24,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2016-09-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(9,15,'2016-09-27 14:40:01','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":7,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"243\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"DocDateFrom\":\"2016-05-12\",\"DocDateUntil\":\"\"}}'),(10,12,'2016-09-27 16:52:46','Shipment','ModuleAbstract:ExportToExcel','{\"count\":2,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-04-01\",\"MonthUntil\":\"2016-06-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(11,12,'2016-09-27 16:53:03','Shipment','ModuleAbstract:ExportToExcel','{\"count\":24,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-04-01\",\"MonthUntil\":\"2016-06-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(12,15,'2016-09-29 07:42:59','Shipment','ModuleAbstract:ExportToExcel','{\"count\":375,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-08-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(13,15,'2016-10-03 08:07:36','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":18,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"VADIK\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"2016-01-01\",\"DocDateUntil\":\"2016-10-03\"}}'),(14,12,'2016-10-03 11:46:20','Shipment','ModuleAbstract:ExportToExcel','{\"count\":3,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"332\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-09-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(15,12,'2016-10-03 11:50:40','Shipment','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"330\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-09-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(16,12,'2016-10-03 11:56:12','Shipment','ModuleAbstract:ExportToExcel','{\"count\":72,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"580-\",\"TrafficID\":\"1\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"1\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-09-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(17,15,'2016-10-03 14:33:58','Shipment','ModuleAbstract:ExportToExcel','{\"count\":57,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-09-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(18,15,'2016-10-05 07:46:41','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":8,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"243\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"DocDateFrom\":\"2016-05-12\",\"DocDateUntil\":\"\"}}'),(19,12,'2016-10-06 12:42:13','Shipment','ModuleAbstract:ExportToExcel','{\"count\":30,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-07-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(20,9,'2016-10-06 13:56:11','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":982,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"MSH\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(21,9,'2016-10-11 16:09:54','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":1,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"sz\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(22,9,'2016-10-11 20:03:44','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":34,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"sz\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(23,9,'2016-10-11 20:09:12','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":34,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"sz\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(24,10,'2016-10-12 05:55:22','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":41,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"34\",\"VendorAccountID\":\"%\",\"Reference\":\"\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"EUR\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"Open\",\"Status11\":1,\"Status12\":1,\"Status13\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(25,9,'2016-10-12 15:07:33','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"65\",\"VendorAccountID\":\"%\",\"Reference\":\"\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(26,10,'2016-10-16 19:46:08','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":16,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"32\",\"VendorAccountID\":\"%\",\"Reference\":\"\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"EUR\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"Open\",\"Status11\":1,\"Status12\":1,\"Status13\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(27,10,'2016-10-24 20:24:14','Shipment','ModuleAbstract:ExportToExcel','{\"count\":887,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_8\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2014-01-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(28,10,'2016-10-24 20:35:55','Shipment','ModuleAbstract:ExportToExcel','{\"count\":170,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_8\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-01-01\",\"MonthUntil\":\"2015-06-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(29,10,'2016-10-24 20:36:48','Shipment','ModuleAbstract:ExportToExcel','{\"count\":170,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_8\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-01-01\",\"MonthUntil\":\"2015-06-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(30,10,'2016-10-24 20:56:40','Shipment','ModuleAbstract:ExportToExcel','{\"count\":140,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_8\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-01-01\",\"MonthUntil\":\"2015-05-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(31,10,'2016-10-24 21:35:28','Shipment','ModuleAbstract:ExportToExcel','{\"count\":30,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_8\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-06-01\",\"MonthUntil\":\"2015-06-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(32,10,'2016-10-24 21:37:39','Shipment','ModuleAbstract:ExportToExcel','{\"count\":82,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_8\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-06-01\",\"MonthUntil\":\"2015-08-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(33,10,'2016-10-25 14:19:51','Shipment','ModuleAbstract:ExportToExcel','{\"count\":31,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"297\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-04-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(34,12,'2016-10-27 07:27:10','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":8,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"32\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(35,15,'2016-10-27 08:25:13','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":10,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"243\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-12\",\"DocDateUntil\":\"\"}}'),(36,15,'2016-10-28 10:03:13','Shipment','ModuleAbstract:ExportToExcel','{\"count\":27,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2016-10-01\",\"MonthUntil\":\"2016-10-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(37,15,'2016-10-31 14:22:06','Shipment','ModuleAbstract:ExportToExcel','{\"count\":58,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-10-01\",\"MonthUntil\":\"2016-10-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(38,15,'2016-11-03 08:14:43','Shipment','ModuleAbstract:ExportToExcel','{\"count\":20,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"219\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-09-01\",\"MonthUntil\":\"2016-11-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(39,10,'2016-11-03 13:35:37','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":5,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"Open\",\"Overdue\":\"%\",\"ResponsibleSales\":\"9\",\"Status11\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(40,9,'2016-11-04 18:18:23','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":3,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"32\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"USD\",\"Status\":\"Open\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(41,9,'2016-11-04 18:18:34','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":10,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"32\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"USD\",\"Status\":\"Open\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(42,12,'2016-11-05 16:52:33','Shipment','ModuleAbstract:ExportToExcel','{\"count\":5,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"330\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-10-01\",\"MonthUntil\":\"2016-10-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(43,9,'2016-11-07 00:00:55','Shipment','ModuleAbstract:ExportToExcel','{\"count\":58,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-10-01\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(44,15,'2016-11-14 13:53:41','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"232\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-12\",\"DocDateUntil\":\"\"}}'),(45,10,'2016-11-16 08:27:01','Shipment','ModuleAbstract:ExportToExcel','{\"count\":19,\"filterValues\":{\"FileNumbers\":\"107090-107094, 107121, 107123-107128, 107134-107140\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(46,10,'2016-11-16 19:11:20','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":9,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"SZAE16060002\\nSZAE16060003\\nSZAE16060035\\nSZAE16060049\\nSZAE16070053\\nSZAE16070048\\nSZAE16080035\\nSZAE16080059\\nSZAE16080055\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(47,10,'2016-11-16 19:11:27','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":9,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"SZAE16060002\\nSZAE16060003\\nSZAE16060035\\nSZAE16060049\\nSZAE16070053\\nSZAE16070048\\nSZAE16080035\\nSZAE16080059\\nSZAE16080055\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(48,10,'2016-11-17 20:27:44','Shipment','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"FileNumbers\":\"107148-107151\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(49,12,'2016-11-24 08:05:17','Shipment','ModuleAbstract:ExportToExcel','{\"count\":30,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"459\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(50,15,'2016-11-28 09:22:02','Shipment','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"SGN\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"0\",\"MonthFrom\":\"2016-11-01\",\"MonthUntil\":\"2016-11-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(51,15,'2016-11-28 13:11:04','Shipment','ModuleAbstract:ExportToExcel','{\"count\":45,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2016-11-01\",\"MonthUntil\":\"2016-11-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(52,15,'2016-11-29 14:37:32','Shipment','ModuleAbstract:ExportToExcel','{\"count\":69,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-11-01\",\"MonthUntil\":\"2016-11-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(53,15,'2016-12-01 08:15:01','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":27,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"vadik\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"2016-01-01\",\"DocDateUntil\":\"2016-12-01\"}}'),(54,12,'2016-12-02 13:52:53','Shipment','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"1\",\"CompanyID\":\"%\",\"CustomerID\":\"330\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-11-01\",\"MonthUntil\":\"2016-11-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(55,15,'2016-12-05 09:50:29','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"243\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-12\",\"DocDateUntil\":\"\"}}'),(56,10,'2016-12-05 12:31:38','Shipment','ModuleAbstract:ExportToExcel','{\"count\":3,\"filterValues\":{\"FileNumbers\":\"200875-200877\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(57,10,'2016-12-05 13:52:20','Shipment','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"FileNumbers\":\"107272-107276, 107258\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(58,10,'2016-12-05 23:37:38','Shipment','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"FileNumbers\":\"107219-107222\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(59,10,'2016-12-06 14:17:54','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"34\",\"VendorAccountID\":\"%\",\"Reference\":\"\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"Open\",\"Status11\":1,\"Status12\":1,\"Status13\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(60,9,'2016-12-08 23:33:55','Shipment','ModuleAbstract:ExportToExcel','{\"count\":59,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2016-11-01\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(61,9,'2016-12-08 23:41:42','Shipment','ModuleAbstract:ExportToExcel','{\"count\":59,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2016-11-01\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(62,12,'2016-12-12 10:47:29','Shipment','ModuleAbstract:ExportToExcel','{\"count\":83,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"580\",\"TrafficID\":\"1\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-10-01\",\"MonthUntil\":\"2016-10-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(63,12,'2016-12-12 11:29:53','Shipment','ModuleAbstract:ExportToExcel','{\"count\":92,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"580\",\"TrafficID\":\"1\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"1\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-11-01\",\"MonthUntil\":\"2016-11-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(64,12,'2016-12-15 17:08:33','Shipment','ModuleAbstract:ExportToExcel','{\"count\":30,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-07-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(65,15,'2016-12-16 12:47:26','Shipment','ModuleAbstract:ExportToExcel','{\"count\":34,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(66,10,'2016-12-17 17:48:54','Shipment','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"FileNumbers\":\"107347-107350\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(67,7,'2016-12-19 14:11:27','Shipment','ModuleAbstract:ExportToExcel','{\"count\":31,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"1\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(68,12,'2016-12-19 16:20:38','Shipment','ModuleAbstract:ExportToExcel','{\"count\":30,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-07-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(69,12,'2016-12-19 16:24:34','Shipment','ModuleAbstract:ExportToExcel','{\"count\":30,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-07-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(70,12,'2016-12-19 16:25:23','Shipment','ModuleAbstract:ExportToExcel','{\"count\":30,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-07-01\",\"MonthUntil\":\"2016-09-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(71,10,'2016-12-19 16:48:28','Shipment','ModuleAbstract:ExportToExcel','{\"count\":5,\"filterValues\":{\"FileNumbers\":\"107381-107385\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(72,12,'2016-12-20 06:11:09','Shipment','ModuleAbstract:ExportToExcel','{\"count\":57,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"1\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-01-01\",\"MonthUntil\":\"2015-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(73,10,'2016-12-21 15:56:25','Shipment','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"FileNumbers\":\"107400-107402, 107391\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(74,12,'2016-12-22 14:06:36','Shipment','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"302\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(75,12,'2016-12-22 14:45:51','Shipment','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"330\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(76,12,'2016-12-22 14:46:17','Shipment','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"330\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(77,15,'2016-12-23 10:54:26','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":10,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"232\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-01\",\"DocDateUntil\":\"\"}}'),(78,9,'2016-12-24 20:33:14','Shipment','ModuleAbstract:ExportToExcel','{\"count\":69,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-11-01\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(79,15,'2016-12-26 09:58:23','Shipment','ModuleAbstract:ExportToExcel','{\"count\":61,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(80,15,'2016-12-26 10:05:22','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":9,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"vadik\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"2016-06-01\",\"DocDateUntil\":\"2016-12-31\"}}'),(81,15,'2016-12-26 10:05:53','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":10,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"219\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-01\",\"DocDateUntil\":\"\"}}'),(82,15,'2016-12-26 13:11:55','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":10,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"219\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-01\",\"DocDateUntil\":\"\"}}'),(83,15,'2016-12-26 14:29:08','Shipment','ModuleAbstract:ExportToExcel','{\"count\":639,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(84,15,'2016-12-26 14:29:50','Shipment','ModuleAbstract:ExportToExcel','{\"count\":598,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(85,15,'2016-12-26 14:32:25','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":5,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"243\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-01\",\"DocDateUntil\":\"\"}}'),(86,15,'2016-12-26 14:38:50','Shipment','ModuleAbstract:ExportToExcel','{\"count\":41,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"0\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(87,14,'2016-12-26 14:49:37','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":7,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"Ukh\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(88,14,'2016-12-26 14:52:58','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"aive\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(89,14,'2016-12-26 14:55:23','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":57,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"kao\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(90,14,'2016-12-26 14:59:11','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":7,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"ukh\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(91,14,'2016-12-26 15:00:35','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"aiven\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(92,14,'2016-12-26 15:02:51','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":12,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"oksa\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(93,14,'2016-12-26 15:10:35','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":20,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"labt\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(94,15,'2016-12-27 09:36:02','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":2,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"393\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-01\",\"DocDateUntil\":\"\"}}'),(95,15,'2016-12-27 09:37:49','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":2,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"248\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2016-05-01\",\"DocDateUntil\":\"\"}}'),(96,12,'2016-12-27 11:43:19','Shipment','ModuleAbstract:ExportToExcel','{\"count\":118,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"580-\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(97,12,'2016-12-29 06:09:48','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":147,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"SK\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(98,12,'2016-12-29 07:07:40','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":44,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"SK\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"555-\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(99,12,'2016-12-29 07:39:38','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":25,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"SK\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(100,12,'2016-12-29 07:39:54','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":148,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"SK\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(101,12,'2016-12-29 10:02:15','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":148,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"SK\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(102,15,'2016-12-29 14:00:27','Shipment','ModuleAbstract:ExportToExcel','{\"count\":77,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(103,12,'2016-12-29 14:15:15','Shipment','ModuleAbstract:ExportToExcel','{\"count\":46,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"0\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(104,12,'2016-12-29 14:18:14','Shipment','ModuleAbstract:ExportToExcel','{\"count\":46,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"0\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(105,15,'2016-12-30 08:15:19','Shipment','ModuleAbstract:ExportToExcel','{\"count\":78,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(106,15,'2016-12-30 08:24:04','Shipment','ModuleAbstract:ExportToExcel','{\"count\":635,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(107,9,'2017-01-09 21:24:41','Shipment','ModuleAbstract:ExportToExcel','{\"count\":300,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2015-08-01\",\"MonthUntil\":\"2015-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(108,15,'2017-01-12 12:10:51','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":20,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"labtech\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"13\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"2016-02-01\",\"DocDateUntil\":\"2016-12-31\"}}'),(109,9,'2017-01-12 13:22:03','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":73,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"214\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(110,9,'2017-01-12 13:23:02','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":48,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"241\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(111,9,'2017-01-12 13:29:47','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":30,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"15\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2015-01-01\",\"DocDateUntil\":\"2015-12-31\"}}'),(112,9,'2017-01-12 13:30:29','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":7,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"14\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2015-01-01\",\"DocDateUntil\":\"2015-12-31\"}}'),(113,9,'2017-01-12 13:31:26','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":182,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"14\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2015-01-01\",\"DocDateUntil\":\"2015-12-31\"}}'),(114,9,'2017-01-12 13:31:46','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":336,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"15\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2015-01-01\",\"DocDateUntil\":\"2015-12-31\"}}'),(115,9,'2017-01-12 13:36:34','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":416,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"15\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2014-01-01\",\"DocDateUntil\":\"2014-12-31\"}}'),(116,9,'2017-01-12 13:36:51','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":416,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"15\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2014-01-01\",\"DocDateUntil\":\"2014-12-31\"}}'),(117,9,'2017-01-12 13:37:10','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":129,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"14\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2014-01-01\",\"DocDateUntil\":\"2014-12-31\"}}'),(118,15,'2017-01-13 12:55:34','Shipment','ModuleAbstract:ExportToExcel','{\"count\":692,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2014-01-01\",\"MonthUntil\":\"2014-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(119,15,'2017-01-13 13:57:15','Shipment','ModuleAbstract:ExportToExcel','{\"count\":635,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(120,14,'2017-01-13 14:31:18','Shipment','ModuleAbstract:ExportToExcel','{\"count\":40,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"214\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2014-01-01\",\"MonthUntil\":\"2017-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(121,14,'2017-01-13 14:40:07','Shipment','ModuleAbstract:ExportToExcel','{\"count\":28,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"241\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2014-01-01\",\"MonthUntil\":\"2017-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(122,10,'2017-01-20 03:10:32','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":8,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"580 - 1148 8746\\n580 - 1148 8772\\n580 - 1148 8901\\n580 - 1148 8912\\n555 - 8628 8090\\n580 - 1148 8864 \\n555 - 8628 8101  \\n580 - 1148 8853\\n580 - 1148 8794\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(123,9,'2017-01-23 15:18:34','Shipment','ModuleAbstract:ExportToExcel','{\"count\":692,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2014-01-01\",\"MonthUntil\":\"2014-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(124,10,'2017-01-23 19:09:38','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":17,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"580 - 1162 9015\\n580 - 1162 9026\\n580 - 1162 8735\\n580 - 1191 8594\\n580 - 1191 8605\\n580 - 1191 8616\\n580 - 1191 8620\\n580 - 1162 8945\\n580 - 1191 8690\\n580 - 1191 8701\\n580 - 1191 8745\\n580 - 1162 8816\\n580 - 1191 8712\\n580 - 1162 8820\\n580 - 1162 8746\\n580 - 1191 8756\\n580 - 1162 8750\\n580 - 1006 6254\\n580 - 1006 6265\\n580 - 1006 6291\\n580 - 1088 0962\\n580 - 1088 0940\\n580 - 1006 6276\\n580 - 1006 6280\\n580 - 1021 9963\\n580 - 1088 0984\\n580 - 1088 0951\\n580 - 1088 0995\\n580 - 1088 1006\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(125,10,'2017-01-25 20:38:40','Shipment','ModuleAbstract:ExportToExcel','{\"count\":217,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"HKG\",\"DepartureLocationName\":\"HONG KONG INT\'L\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-01-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(126,10,'2017-01-26 08:49:31','Shipment','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"FileNumbers\":\"107564-107567\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(127,15,'2017-01-26 13:41:34','Shipment','ModuleAbstract:ExportToExcel','{\"count\":39,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(128,12,'2017-01-30 08:40:11','Shipment','ModuleAbstract:ExportToExcel','{\"count\":1,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"330\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(129,9,'2017-01-30 12:44:11','Customer','ModuleAbstract:ExportToExcel','{\"count\":107,\"filterValues\":{\"FilterName\":\"\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"Active\":\"%\"}}'),(130,15,'2017-01-31 09:36:07','Shipment','ModuleAbstract:ExportToExcel','{\"count\":46,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(131,10,'2017-01-31 13:12:13','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(132,10,'2017-01-31 13:15:30','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_14\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(133,10,'2017-01-31 13:21:17','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_14\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(134,10,'2017-01-31 13:23:27','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":24,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"176-35698821\\n176-35722304\\n176-32192661\\n176-38532793\\n176-38532782\\n176-35945416\\n176-25591171\\n176-25590670\\n176-25591145\\n176-25591252\\n176-25591182\\n176-25591160\\n176-25590681\\n176-33493460\\n176-31080453\\n176-67692811\\n105-60089201\\n125-34568936\\n176-31073173\\n125-36505291\\n724-56321775\\n020-60242416\\n020-60243072\\n105-51248341\\n176-36032172\\n020-98076451\\n724-57329591\\n176-31080556\\n580-12744384\\n176-35698261\\n250-50399764\\n628-04168194\\n176-35698471\\n176-35698401\\n176-38251146\\n176-35659912\\n176-31081374\\n176-36031726\\n176-31080475\\n176-38532384\\n064-89010036\\n176-31080490\\n999-48317603\\n176-33754464\\n176-33743964\\n074-53476920\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(135,10,'2017-01-31 13:24:24','Shipment','ModuleAbstract:ExportToExcel','{\"count\":24,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"176-31080556\\n176-38251146\\n176-35698471\\n176-35698401\\n176-25591171\\n176-31073173\\n176-35659912\\n176-25591182\\n176-31081374\\n020-98076451\\n176-36031726\\n176-33493460\\n176-38532793\\n020-60242416\\n724-57329591\\n176-67692811\\n105-51248341\\n176-38532384\\n064-89010036\\n176-25590670\\n176-38532782\\n176-35945416\\n176-25590681\\n074-53476920\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(136,9,'2017-01-31 13:32:07','Shipment','ModuleAbstract:ExportToExcel','{\"count\":46,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(137,10,'2017-01-31 13:46:54','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(138,10,'2017-01-31 13:47:43','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_14\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(139,10,'2017-01-31 13:49:17','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":24,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"125-36505291\\n250-50399764\\n176-35698821\\n176-35698471\\n176-35698401\\n176-38251146\\n176-31080453\\n176-35722304\\n176-32192661\\n724-56321775\\n176-35659912\\n176-31081374\\n176-31080475\\n020-98076451\\n176-36031726\\n176-38532793\\n176-67692811\\n176-38532384\\n176-38532782\\n176-35945416\\n064-89010036\\n724-57329591\\n020-60242416\\n020-60243072\\n176-35698261\\n628-04168194\\n176-31080556\\n580-12744384\\n999-48317603\\n176-31080490\\n176-33754464\\n176-36032172\\n105-60089201\\n176-25591252\\n176-25591182\\n176-25591171\\n125-34568936\\n176-33493460\\n176-33743964\\n105-51248341\\n176-25591160\\n176-31073173\\n176-25590670\\n176-25590681\\n176-25591145\\n074-53476920\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(140,10,'2017-01-31 13:50:14','Shipment','ModuleAbstract:ExportToExcel','{\"count\":24,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"176-31080556\\n176-38251146\\n176-35698471\\n176-35698401\\n176-25591171\\n176-31073173\\n176-35659912\\n176-25591182\\n176-31081374\\n020-98076451\\n176-36031726\\n176-33493460\\n176-38532793\\n020-60242416\\n724-57329591\\n176-67692811\\n105-51248341\\n176-38532384\\n064-89010036\\n176-25590670\\n176-38532782\\n176-35945416\\n176-25590681\\n074-53476920\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(141,10,'2017-02-01 19:57:22','Shipment','ModuleAbstract:ExportToExcel','{\"count\":168,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(142,12,'2017-02-02 11:49:08','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":15,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"SK\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(143,9,'2017-02-02 12:38:42','CustomerAccount','ModuleAbstract:ExportToExcel','{\"count\":201,\"filterValues\":{\"FilterName\":\"\",\"BUIDsFilter\":\"2\"}}'),(144,10,'2017-02-02 20:30:18','Shipment','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"416\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-10-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(145,10,'2017-02-03 10:35:16','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":46,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"24\",\"VendorAccountID\":\"%\",\"Reference\":\"\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"USD\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"Open\",\"Status11\":1,\"Status12\":1,\"Status13\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"2017-01-15\"}}'),(146,10,'2017-02-03 10:41:54','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":50,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"24\",\"VendorAccountID\":\"%\",\"Reference\":\"\",\"ExactReference\":0,\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"USD\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"Open\",\"Status11\":1,\"Status12\":1,\"Status13\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"2017-01-19\"}}'),(147,23,'2017-02-03 14:10:06','ItemList','ModuleAbstract:ExportToExcel','{\"count\":89,\"filterValues\":{\"FilterName\":\"\"}}'),(148,9,'2017-02-05 21:30:17','Shipment','ModuleAbstract:ExportToExcel','{\"count\":50,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-10-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(149,9,'2017-02-05 21:52:19','Shipment','ModuleAbstract:ExportToExcel','{\"count\":46,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(150,10,'2017-02-06 12:54:08','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(151,10,'2017-02-06 12:56:58','Shipment','ModuleAbstract:ExportToExcel','{\"count\":22,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(152,10,'2017-02-06 12:57:21','Shipment','ModuleAbstract:ExportToExcel','{\"count\":22,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(153,10,'2017-02-06 12:57:28','Shipment','ModuleAbstract:ExportToExcel','{\"count\":22,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(154,10,'2017-02-06 12:59:00','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_14\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(155,10,'2017-02-06 13:00:38','Shipment','ModuleAbstract:ExportToExcel','{\"count\":20,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_14\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(156,9,'2017-02-06 16:53:46','Shipment','ModuleAbstract:ExportToExcel','{\"count\":3,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"232\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-12-01\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(157,12,'2017-02-08 09:09:39','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":11,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"435\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"%\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(158,9,'2017-02-10 07:42:02','Shipment','ModuleAbstract:ExportToExcel','{\"count\":45,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2016-10-01\",\"MonthUntil\":\"2016-12-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(159,9,'2017-02-10 08:21:24','Shipment','ModuleAbstract:ExportToExcel','{\"count\":17,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_12\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(160,10,'2017-02-12 14:46:39','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":79,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"703038\\n717268\\n718023\\n722169\\n727231\\n728637\\n729196\\n730802\\n731995\\n731997\\n734687\\n735823\\n738198\\n738009\\n739380\\n740582\\n740570\\n740584\\n741040\\n742250\\n743132\\n743174\\n743184\\n742873\\n744861\\n745458\\n746432\\n746434\\n746606\\n746410\\n747708\\n748122\\n748433\\n748508\\n748773\\n748860\\n749720\\n749941\\n750350\\n750363\\n751517\\n751523\\n752244\\n752345\\n753056\\n753030\\n752835\\n753767\\n754074\\n754550\\n754707\\n754891\\n755487\\n756599\\n756379\\n756583\\n757001\\n757226\\n757319\\n757318\\n757420\\n757688\\n758844\\n758823\\n759182\\n759266\\n759129\\n760626\\n760610\\n760618\\n760831\\n761338\\n761336\\n761446\\n761675\\n761544\\n761753\\n762211\\n762383\\n762487\\n762825\\n763131\\n762848\\n763350\\n763938\\n764212\\n764392\\n764726\\n764727\\n764765\\n764858\\n765834\",\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(161,23,'2017-02-21 13:48:07','Shipment','ModuleAbstract:ExportToExcel','{\"count\":203,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"302\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(162,14,'2017-02-22 09:44:12','Shipment','ModuleAbstract:ExportToExcel','{\"count\":50,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(163,15,'2017-02-27 09:49:55','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":38,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2017-01-01\",\"DocDateUntil\":\"2017-02-28\"}}'),(164,12,'2017-02-28 08:39:28','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":8,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"%\",\"VendorAccountID\":\"%\",\"Reference\":\"sk\",\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"NotDue\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"%\",\"Status11\":0,\"Status12\":0,\"Status13\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(165,10,'2017-02-28 12:40:06','VInvoice','ModuleAbstract:ExportToExcel','{\"count\":20,\"filterValues\":{\"CompanyID\":\"%\",\"VendorID\":\"33\",\"VendorAccountID\":\"%\",\"Reference\":\"fz\",\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"CurrencyISO\":\"%\",\"Overdue\":\"%\",\"Responsible\":\"%\",\"HasVAT\":\"%\",\"Status\":\"Open\",\"Status11\":1,\"Status12\":1,\"Status13\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"ReceivedDateFrom\":\"\",\"ReceivedDateUntil\":\"\",\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(166,15,'2017-02-28 15:15:19','Shipment','ModuleAbstract:ExportToExcel','{\"count\":51,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"1\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(167,15,'2017-03-03 09:08:31','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":3,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"219\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2017-01-01\",\"DocDateUntil\":\"2017-03-03\"}}'),(168,15,'2017-03-03 09:20:27','Shipment','ModuleAbstract:ExportToExcel','{\"count\":61,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(169,10,'2017-03-05 13:40:26','Shipment','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_10\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(170,10,'2017-03-05 13:42:55','Shipment','ModuleAbstract:ExportToExcel','{\"count\":4,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_10\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(171,10,'2017-03-05 13:44:06','Shipment','ModuleAbstract:ExportToExcel','{\"count\":3,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_10\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(172,15,'2017-03-06 08:51:10','Shipment','ModuleAbstract:ExportToExcel','{\"count\":41,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(173,12,'2017-03-07 12:42:19','Shipment','ModuleAbstract:ExportToExcel','{\"count\":203,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"302\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(174,12,'2017-03-07 12:42:23','Shipment','ModuleAbstract:ExportToExcel','{\"count\":203,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"302\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(175,12,'2017-03-07 12:42:30','Shipment','ModuleAbstract:ExportToExcel','{\"count\":2,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"302\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(176,12,'2017-03-07 12:45:15','Shipment','ModuleAbstract:ExportToExcel','{\"count\":7,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"330\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(177,9,'2017-03-08 09:59:43','Shipment','ModuleAbstract:ExportToExcel','{\"count\":41,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"2\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(178,10,'2017-03-09 17:54:19','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":7,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"429\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"EUR\",\"Status\":\"Open\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":1,\"Status21\":1,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"\",\"DocDateUntil\":\"\"}}'),(179,10,'2017-03-10 00:12:06','Shipment','ModuleAbstract:ExportToExcel','{\"count\":15,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_14\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(180,10,'2017-03-10 00:15:04','Shipment','ModuleAbstract:ExportToExcel','{\"count\":23,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(181,10,'2017-03-10 00:17:38','Shipment','ModuleAbstract:ExportToExcel','{\"count\":3,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"343\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(182,15,'2017-03-10 09:01:34','Shipment','ModuleAbstract:ExportToExcel','{\"count\":41,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(183,15,'2017-03-10 09:17:44','Shipment','ModuleAbstract:ExportToExcel','{\"count\":41,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(184,15,'2017-03-10 12:57:31','Shipment','ModuleAbstract:ExportToExcel','{\"count\":41,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(185,10,'2017-03-10 13:01:58','Shipment','ModuleAbstract:ExportToExcel','{\"count\":16,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_14\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(186,10,'2017-03-10 13:03:12','Shipment','ModuleAbstract:ExportToExcel','{\"count\":22,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_15\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(187,10,'2017-03-10 13:04:34','Shipment','ModuleAbstract:ExportToExcel','{\"count\":3,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"343\",\"CustomerAccountID\":\"%\",\"Responsible\":\"ResponsibleSales_10\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-01-01\",\"MonthUntil\":\"2017-01-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(188,15,'2017-03-13 09:08:08','Shipment','ModuleAbstract:ExportToExcel','{\"count\":2,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"378\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"11\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-03-01\",\"MonthUntil\":\"2017-03-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(189,15,'2017-03-13 09:08:26','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":2,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"378\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2017-01-01\",\"DocDateUntil\":\"2017-03-13\"}}'),(190,15,'2017-03-13 09:12:35','CInvoice','ModuleAbstract:ExportToExcel','{\"count\":6,\"filterValues\":{\"CompanyID\":\"%\",\"CustomerID\":\"243\",\"CustomerAccountID\":\"%\",\"InvoiceNumbers\":\"\",\"FileNumbers\":\"\",\"ShippingDocuments\":\"\",\"CurrencyISO\":\"%\",\"Status\":\"11\",\"Overdue\":\"%\",\"ResponsibleSales\":\"%\",\"Status11\":0,\"Status21\":0,\"Status22\":0,\"Status31\":0,\"Status32\":0,\"DocDateFrom\":\"2017-01-01\",\"DocDateUntil\":\"2017-03-13\"}}'),(191,12,'2017-03-13 14:02:32','Shipment','ModuleAbstract:ExportToExcel','{\"count\":171,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"%\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-02-01\",\"MonthUntil\":\"2017-02-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}'),(192,10,'2017-03-18 18:33:39','Shipment','ModuleAbstract:ExportToExcel','{\"count\":11,\"filterValues\":{\"FileNumbers\":\"\",\"MAWBNumbers\":\"\",\"TrafficID\":\"%\",\"CompanyID\":\"%\",\"CustomerID\":\"343\",\"CustomerAccountID\":\"%\",\"Responsible\":\"%\",\"BUIDsFilter\":\"%\",\"DepartureIATA\":\"\",\"DepartureLocationName\":\"\",\"ArrivalIATA\":\"\",\"ArrivalLocationName\":\"\",\"Comments\":\"\",\"CriterionID\":\"%\",\"FileClosed\":\"%\",\"MonthFrom\":\"2017-03-01\",\"MonthUntil\":\"2017-03-01\",\"Color\":0,\"FlightNo\":\"\",\"FlightDate\":\"\",\"HasVAT\":\"%\",\"ETDFrom\":\"\",\"ETDUntil\":\"\",\"ETAFrom\":\"\",\"ETAUntil\":\"\",\"ATAFrom\":\"\",\"ATAUntil\":\"\",\"ReadyForCarriageDateFrom\":\"\",\"ReadyForCarriageDateUntil\":\"\"}}');
/*!40000 ALTER TABLE `usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `ID` int(5) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT '0',
  `CreatedDateTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Login` varchar(50) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `PasswordPlain` varchar(100) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `GroupID` int(5) NOT NULL DEFAULT '0',
  `Active` tinyint(1) NOT NULL DEFAULT '1',
  `ModuleAccessJSON` text NOT NULL,
  `SpecialAccessJSON` text NOT NULL,
  `Position` varchar(200) NOT NULL DEFAULT '',
  `BirthDay` int(2) NOT NULL,
  `BirthMonth` int(2) NOT NULL,
  `Started` year(4) NOT NULL,
  `BranchOfficeID` int(1) NOT NULL,
  `CurrentVisitDateTime` timestamp NULL DEFAULT NULL,
  `LastVisitDateTime` timestamp NULL DEFAULT NULL,
  `LastRefreshTime` int(11) DEFAULT NULL,
  `ResponsibleRoleIDs` varchar(30) NOT NULL,
  `ResponsibleRoles` varchar(100) NOT NULL,
  `BUIDs` varchar(10) NOT NULL,
  `InnerPhone` varchar(50) NOT NULL,
  `TelegramToken` varchar(50) NOT NULL DEFAULT '',
  `Mobile` varchar(50) NOT NULL,
  `Sex` enum('','m','f') NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` (`ID`, `CreatedBy`, `CreatedDateTime`, `Login`, `Password`, `PasswordPlain`, `Name`, `Email`, `GroupID`, `Active`, `ModuleAccessJSON`, `SpecialAccessJSON`, `Position`, `BirthDay`, `BirthMonth`, `Started`, `BranchOfficeID`, `CurrentVisitDateTime`, `LastVisitDateTime`, `LastRefreshTime`, `ResponsibleRoleIDs`, `ResponsibleRoles`, `BUIDs`, `InnerPhone`, `TelegramToken`, `Mobile`, `Sex`) VALUES ('1', '0', '2011-08-10 18:00:14', 'admin', 'admin', 'adminTelegramToken', 'Admin', 'admin', '5', '1', '{\n    \"Home\": 3,\n    \"Shipment\": 3,\n    \"ShipmentAccounting\": 3,\n    \"Help\": 3,\n    \"Reportmenu\": 3,\n    \"Quotation\": 3,\n    \"DefaultAddons\": 3,\n    \"AirfreightRate\": 3,\n    \"AirfreightSurcharge\": 3,\n    \"SearchAirfreightRateDialog\": 3,\n    \"Customer\": 3,\n    \"CustomerAccount\": 3,\n    \"CInvoice\": 3,\n    \"CPayment\": 3,\n    \"Vendor\": 3,\n    \"VendorAccount\": 3,\n    \"VInvoice\": 3,\n    \"VPayment\": 3,\n    \"Airline\": 3,\n    \"OceanCarrier\": 3,\n    \"Shipper\": 3,\n    \"OurCompany\": 3,\n    \"ContainerType\": 3,\n    \"Uom\": 3,\n    \"ItemList\": 3,\n    \"Traffic\": 3,\n    \"Location\": 3,\n    \"Port\": 3,\n    \"EmailNoticeSettings\": 3,\n    \"CurrencyRateCitadele\": 3,\n    \"Currency\": 3,\n    \"CurrencyRate\": 3,\n    \"Block\": 3,\n    \"ProductionCalendar\": 3,\n    \"Logviewer\": 3,\n    \"Deleted\": 3,\n    \"Autolaunch\": 3,\n    \"EmailQueueModule\": 3,\n    \"EmailLogModule\": 3,\n    \"ColorHint\": 3,\n    \"FiltersLog\": 3,\n    \"Phpinfo\": 3,\n    \"UserModule\": 3,\n    \"Incoterm\": 3,\n    \"ImportCPaymentFrom1CLog\": 3,\n    \"Console\": 3,\n    \"Autolaunchmenu\": 3,\n    \"BU\": 3,\n    \"ResponsibleRole\": 3,\n    \"User\": 3,\n    \"UserGroup\": 3\n}', '{\n    \"Home1\": 1,\n    \"Home2\": 1,\n    \"Home3\": 1,\n    \"SA_HOME_CHECKATD\": 1,\n    \"SA_HOME_CHECKATA\": 1,\n    \"Shipment1\": 1,\n    \"Shipment2\": 1,\n    \"VInvoice1\": 1,\n    \"VInvoice2\": 1,\n    \"VInvoice3\": 1,\n    \"SA_UNBLOCK\": 1,\n    \"Logviewer1\": 1\n}', '', '0', '0', '2000', '0', '2014-12-19 15:46:39', '2014-12-04 11:35:03', '1418997051', '', '|MOW|LED|', '|1|2|', '', 'a', '', '');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_filter`
--

DROP TABLE IF EXISTS `user_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_filter` (
  `CreatedBy` int(10) unsigned NOT NULL,
  `TableName` varchar(50) NOT NULL,
  `FiltersJSON` text NOT NULL,
  `CurrentUID` bigint(20) NOT NULL,
  PRIMARY KEY (`CreatedBy`,`TableName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_filter`
--

LOCK TABLES `user_filter` WRITE;
/*!40000 ALTER TABLE `user_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_filter_history`
--

DROP TABLE IF EXISTS `user_filter_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_filter_history` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `TableID` int(11) NOT NULL,
  `FilterValuesMD5` char(32) NOT NULL,
  `FiltersJSON` mediumtext NOT NULL,
  `Description` text NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `CreatedBy_2` (`CreatedBy`,`TableID`,`FilterValuesMD5`),
  KEY `CreatedBy` (`CreatedBy`,`TableID`,`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_filter_history`
--

LOCK TABLES `user_filter_history` WRITE;
/*!40000 ALTER TABLE `user_filter_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_filter_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_filter_log`
--

DROP TABLE IF EXISTS `user_filter_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_filter_log` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `TableID` int(11) NOT NULL,
  `FieldID` int(11) NOT NULL,
  `Value` varchar(200) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `TableID_FieldID` (`TableID`,`FieldID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_filter_log`
--

LOCK TABLES `user_filter_log` WRITE;
/*!40000 ALTER TABLE `user_filter_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_filter_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_filter_saved`
--

DROP TABLE IF EXISTS `user_filter_saved`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_filter_saved` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) NOT NULL DEFAULT '',
  `TableID` int(11) NOT NULL,
  `FiltersJSON` mediumtext NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `CreatedByTableIDName` (`CreatedBy`,`TableID`,`Name`),
  KEY `CreatedBy` (`CreatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_filter_saved`
--

LOCK TABLES `user_filter_saved` WRITE;
/*!40000 ALTER TABLE `user_filter_saved` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_filter_saved` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_filter_settings`
--

DROP TABLE IF EXISTS `user_filter_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_filter_settings` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `TableID` int(11) NOT NULL,
  `Sort_deprecated` mediumtext NOT NULL,
  `Invisible_deprecated` mediumtext NOT NULL,
  `Defaults` mediumtext NOT NULL,
  `Sort` mediumtext NOT NULL,
  `Invisible` mediumtext NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `CreatedBy_2` (`CreatedBy`,`TableID`),
  KEY `CreatedBy` (`CreatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_filter_settings`
--

LOCK TABLES `user_filter_settings` WRITE;
/*!40000 ALTER TABLE `user_filter_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_filter_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_param`
--

DROP TABLE IF EXISTS `user_param`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_param` (
  `ID` int(7) NOT NULL AUTO_INCREMENT,
  `UserID` int(5) NOT NULL DEFAULT 0,
  `Name` varchar(255) NOT NULL,
  `Value` text NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UserID` (`UserID`,`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_param`
--

LOCK TABLES `user_param` WRITE;
/*!40000 ALTER TABLE `user_param` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_param` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_tablelayout`
--

DROP TABLE IF EXISTS `user_tablelayout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tablelayout` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT 0,
  `LayoutName` varchar(255) NOT NULL DEFAULT '',
  `TableName` varchar(50) NOT NULL DEFAULT '',
  `FieldsShow` text NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UserID` (`CreatedBy`,`LayoutName`,`TableName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_tablelayout`
--

LOCK TABLES `user_tablelayout` WRITE;
/*!40000 ALTER TABLE `user_tablelayout` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_tablelayout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_tableparam`
--

DROP TABLE IF EXISTS `user_tableparam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tableparam` (
  `CreatedBy` int(10) NOT NULL,
  `TableName` varchar(100) NOT NULL,
  `SortFieldKey` varchar(100) DEFAULT NULL,
  `SortOrder` enum('ASC','DESC') DEFAULT NULL,
  `FieldsShow` text DEFAULT NULL,
  `ShowAll` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`CreatedBy`,`TableName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_tableparam`
--

LOCK TABLES `user_tableparam` WRITE;
/*!40000 ALTER TABLE `user_tableparam` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_tableparam` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_visit`
--

DROP TABLE IF EXISTS `user_visit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_visit` (
  `UserID` int(11) NOT NULL,
  `LastLoginDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `LastRefreshDateTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_visit`
--

LOCK TABLES `user_visit` WRITE;
/*!40000 ALTER TABLE `user_visit` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_visit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `userbranchoffice`
--

DROP TABLE IF EXISTS `userbranchoffice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userbranchoffice` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDateTIme` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(200) DEFAULT NULL,
  `Phone` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `userbranchoffice`
--

LOCK TABLES `userbranchoffice` WRITE;
/*!40000 ALTER TABLE `userbranchoffice` DISABLE KEYS */;
/*!40000 ALTER TABLE `userbranchoffice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usergroup`
--

DROP TABLE IF EXISTS `usergroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usergroup` (
  `ID` int(5) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `Description` text COLLATE utf8_unicode_ci NOT NULL,
  `OrderID` int(2) NOT NULL DEFAULT 0,
  `ModuleAccessJSON` text COLLATE utf8_unicode_ci NOT NULL,
  `SpecialAccessJSON` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `OrderID` (`OrderID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci PACK_KEYS=0 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usergroup`
--

LOCK TABLES `usergroup` WRITE;
/*!40000 ALTER TABLE `usergroup` DISABLE KEYS */;
INSERT INTO `usergroup` VALUES (1,0,'2011-03-01 12:16:00','Financial Management','Aprovement and controling of incoming and outgoing payments',1,'{\n    \"Home\": 2,\n    \"Shipment\": 2,\n    \"ShipmentAccounting\": 2,\n    \"Help\": 2,\n    \"Reportmenu\": 2,\n    \"Quotation\": 0,\n    \"DefaultAddons\": 0,\n    \"AirfreightRate\": 0,\n    \"AirfreightSurcharge\": 0,\n    \"SearchAirfreightRateDialog\": 0,\n    \"Customer\": 2,\n    \"CustomerAccount\": 2,\n    \"CInvoice\": 2,\n    \"CPayment\": 2,\n    \"ImportCPaymentFrom1CLog\": 2,\n    \"Vendor\": 2,\n    \"VendorAccount\": 2,\n    \"VInvoice\": 2,\n    \"VPayment\": 2,\n    \"Airline\": 0,\n    \"OceanCarrier\": 0,\n    \"Shipper\": 0,\n    \"OurCompany\": 2,\n    \"ContainerType\": 0,\n    \"Incoterm\": 0,\n    \"Uom\": 0,\n    \"ItemList\": 0,\n    \"Traffic\": 0,\n    \"Location\": 0,\n    \"Port\": 0,\n    \"EmailNoticeSettings\": 0,\n    \"CurrencyRateCitadele\": 0,\n    \"Currency\": 0,\n    \"CurrencyRate\": 2,\n    \"UserModule\": 0,\n    \"Logviewer\": 0,\n    \"Deleted\": 0,\n    \"Autolaunch\": 0,\n    \"QueueEmail\": 0,\n    \"ColorHint\": 0,\n    \"FiltersLog\": 0,\n    \"Console\": 0,\n    \"Autolaunchmenu\": 0,\n    \"BU\": 0,\n    \"Contract\": 0,\n    \"CurrencyModule\": 1,\n    \"Importmenu\": 2,\n    \"PortTerminal\": 1,\n    \"ResponsibleRole\": 0,\n    \"User\": 0,\n    \"UserGroup\": 0\n}','{\n    \"Home1\": 1,\n    \"Home2\": 0,\n    \"Home3\": 0,\n    \"SA_HOME_CHECKATD\": 0,\n    \"SA_HOME_CHECKATA\": 0,\n    \"Shipment1\": 0,\n    \"Shipment2\": 0,\n    \"VInvoice1\": 1,\n    \"VInvoice2\": 1,\n    \"VInvoice3\": 1,\n    \"Logviewer1\": 1\n}'),(2,0,'2011-03-01 12:16:00','Customer Service','',2,'{\n    \"Home\": 2,\n    \"Shipment\": 2,\n    \"ShipmentAccounting\": 2,\n    \"Help\": 2,\n    \"Reportmenu\": 0,\n    \"Quotation\": 0,\n    \"DefaultAddons\": 0,\n    \"AirfreightRate\": 0,\n    \"AirfreightSurcharge\": 0,\n    \"SearchAirfreightRateDialog\": 2,\n    \"Customer\": 2,\n    \"CustomerAccount\": 2,\n    \"CInvoice\": 2,\n    \"CPayment\": 1,\n    \"ImportCPaymentFrom1CLog\": 0,\n    \"Vendor\": 2,\n    \"VendorAccount\": 2,\n    \"VInvoice\": 2,\n    \"VPayment\": 1,\n    \"Airline\": 2,\n    \"OceanCarrier\": 2,\n    \"Shipper\": 2,\n    \"OurCompany\": 0,\n    \"ContainerType\": 0,\n    \"Incoterm\": 0,\n    \"Uom\": 0,\n    \"ItemList\": 2,\n    \"Traffic\": 0,\n    \"Location\": 2,\n    \"Port\": 2,\n    \"EmailNoticeSettings\": 0,\n    \"CurrencyRateCitadele\": 0,\n    \"Currency\": 0,\n    \"CurrencyRate\": 0,\n    \"UserModule\": 0,\n    \"Logviewer\": 0,\n    \"Deleted\": 0,\n    \"Autolaunch\": 0,\n    \"QueueEmail\": 0,\n    \"ColorHint\": 0,\n    \"FiltersLog\": 0,\n    \"Console\": 0,\n    \"Autolaunchmenu\": 0,\n    \"BU\": 0,\n    \"Contract\": 0,\n    \"PortTerminal\": 2,\n    \"ResponsibleRole\": 0,\n    \"User\": 0,\n    \"UserGroup\": 0\n}','{\n    \"Home1\": 0,\n    \"Home2\": 0,\n    \"Home3\": 1,\n    \"SA_HOME_CHECKATD\": 1,\n    \"SA_HOME_CHECKATA\": 1,\n    \"Shipment1\": 0,\n    \"Shipment2\": 0,\n    \"VInvoice1\": 1,\n    \"VInvoice2\": 0,\n    \"VInvoice3\": 0,\n    \"Logviewer1\": 1\n}'),(3,0,'2011-03-01 12:16:00','Sales','',3,'{\n    \"Home\": 2,\n    \"Shipment\": 2,\n    \"ShipmentAccounting\": 2,\n    \"Help\": 2,\n    \"Reportmenu\": 0,\n    \"Quotation\": 2,\n    \"DefaultAddons\": 2,\n    \"AirfreightRate\": 2,\n    \"AirfreightSurcharge\": 2,\n    \"SearchAirfreightRateDialog\": 2,\n    \"Customer\": 2,\n    \"CustomerAccount\": 2,\n    \"CInvoice\": 2,\n    \"CPayment\": 2,\n    \"ImportCPaymentFrom1CLog\": 0,\n    \"Vendor\": 2,\n    \"VendorAccount\": 2,\n    \"VInvoice\": 2,\n    \"VPayment\": 2,\n    \"Airline\": 2,\n    \"OceanCarrier\": 2,\n    \"Shipper\": 2,\n    \"OurCompany\": 2,\n    \"ContainerType\": 2,\n    \"Incoterm\": 2,\n    \"Uom\": 2,\n    \"ItemList\": 2,\n    \"Traffic\": 2,\n    \"Location\": 2,\n    \"Port\": 2,\n    \"EmailNoticeSettings\": 2,\n    \"CurrencyRateCitadele\": 0,\n    \"Currency\": 2,\n    \"CurrencyRate\": 2,\n    \"UserModule\": 0,\n    \"Logviewer\": 0,\n    \"Deleted\": 0,\n    \"Autolaunch\": 0,\n    \"QueueEmail\": 0,\n    \"ColorHint\": 0,\n    \"FiltersLog\": 0,\n    \"Console\": 0,\n    \"Autolaunchmenu\": 0,\n    \"BU\": 2,\n    \"Contract\": 0,\n    \"PortTerminal\": 2,\n    \"ResponsibleRole\": 0,\n    \"User\": 0,\n    \"UserGroup\": 0\n}','{\n    \"Home1\": 0,\n    \"Home2\": 0,\n    \"Home3\": 1,\n    \"SA_HOME_CHECKATD\": 1,\n    \"SA_HOME_CHECKATA\": 1,\n    \"Shipment1\": 0,\n    \"Shipment2\": 0,\n    \"VInvoice1\": 1,\n    \"VInvoice2\": 0,\n    \"VInvoice3\": 0,\n    \"Logviewer1\": 1\n}'),(5,0,'2011-03-01 12:16:00','Administrators','',5,'{\n    \"Home\": 2,\n    \"Shipment\": 2,\n    \"ShipmentAccounting\": 2,\n    \"Help\": 2,\n    \"Reportmenu\": 2,\n    \"Quotation\": 2,\n    \"DefaultAddons\": 2,\n    \"AirfreightRate\": 2,\n    \"AirfreightSurcharge\": 2,\n    \"SearchAirfreightRateDialog\": 2,\n    \"Customer\": 2,\n    \"CustomerAccount\": 2,\n    \"CInvoice\": 2,\n    \"CPayment\": 2,\n    \"ImportCPaymentFrom1CLog\": 2,\n    \"Vendor\": 2,\n    \"VendorAccount\": 2,\n    \"VInvoice\": 2,\n    \"VPayment\": 2,\n    \"Airline\": 2,\n    \"OceanCarrier\": 2,\n    \"Shipper\": 2,\n    \"OurCompany\": 2,\n    \"ContainerType\": 2,\n    \"Incoterm\": 2,\n    \"Uom\": 2,\n    \"ItemList\": 2,\n    \"Traffic\": 2,\n    \"Location\": 2,\n    \"Port\": 2,\n    \"EmailNoticeSettings\": 2,\n    \"CurrencyRateCitadele\": 2,\n    \"Currency\": 2,\n    \"CurrencyRate\": 2,\n    \"Block\": 2,\n    \"Logviewer\": 2,\n    \"Deleted\": 2,\n    \"Autolaunch\": 2,\n    \"EmailQueueModule\": 2,\n    \"EmailLogModule\": 2,\n    \"ColorHint\": 2,\n    \"FiltersLog\": 2,\n    \"UserModule\": 2,\n    \"QueueEmail\": 0,\n    \"Console\": 2,\n    \"Autolaunchmenu\": 2,\n    \"BU\": 2,\n    \"BuyingRates\": 2,\n    \"Contract\": 2,\n    \"CurrencyModule\": 2,\n    \"Holiday\": 2,\n    \"Importmenu\": 0,\n    \"ListSurcharges\": 2,\n    \"OurAccount\": 2,\n    \"PortTerminal\": 2,\n    \"RateMain\": 2,\n    \"RateSurcharge\": 2,\n    \"RateSurchargeWeight\": 2,\n    \"RateTAD\": 2,\n    \"ResponsibleRole\": 2,\n    \"User\": 2,\n    \"UserGroup\": 2\n}','{\n    \"Home1\": 1,\n    \"Home2\": 1,\n    \"Home3\": 1,\n    \"SA_HOME_CHECKATD\": 1,\n    \"SA_HOME_CHECKATA\": 1,\n    \"Shipment1\": 1,\n    \"Shipment2\": 1,\n    \"VInvoice1\": 1,\n    \"VInvoice2\": 1,\n    \"VInvoice3\": 1,\n    \"SA_UNBLOCK\": 1,\n    \"Logviewer1\": 1,\n    \"CInvoice5\": 1,\n    \"Customer1\": 1,\n    \"Customer2\": 0\n}');
/*!40000 ALTER TABLE `usergroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vcontact`
--

DROP TABLE IF EXISTS `vcontact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vcontact` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL DEFAULT 0,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `VendorID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Position` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `WorkPhone` varchar(100) NOT NULL,
  `MobilePhone` varchar(100) NOT NULL,
  `Comments` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `VendorID` (`VendorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vcontact`
--

LOCK TABLES `vcontact` WRITE;
/*!40000 ALTER TABLE `vcontact` DISABLE KEYS */;
/*!40000 ALTER TABLE `vcontact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor`
--

DROP TABLE IF EXISTS `vendor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor` (
  `ID` int(6) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  `Address` text NOT NULL,
  `Contacts` text NOT NULL,
  `isAirline` tinyint(1) NOT NULL,
  `isCarrier` tinyint(1) NOT NULL,
  `isPortExpeditor` tinyint(1) NOT NULL,
  `isTrucker` tinyint(1) NOT NULL,
  `CompanyID` int(11) NOT NULL,
  `CreditPeriod` int(3) NOT NULL,
  `CreditAmount` int(9) NOT NULL,
  `CreditCurrencyISO` char(3) CHARACTER SET ascii NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `Comments` text NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor`
--

LOCK TABLES `vendor` WRITE;
/*!40000 ALTER TABLE `vendor` DISABLE KEYS */;
INSERT INTO `vendor` VALUES (100,9,'2014-05-28 16:54:35','ELION OOO','BOROVAYA STR. 44, A, ROOM H\n192007 ST.PETERSBURG, RUSSIA','',0,0,0,1,1,14,10000,'USD',1,'');
/*!40000 ALTER TABLE `vendor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_responsible`
--

DROP TABLE IF EXISTS `vendor_responsible`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_responsible` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `VendorID` int(6) NOT NULL,
  `UserID` int(5) NOT NULL,
  `RoleID` int(3) NOT NULL,
  `Role` enum('CS','Sales') NOT NULL DEFAULT 'CS',
  PRIMARY KEY (`ID`),
  KEY `VendorID_UserID_RoleID` (`VendorID`,`UserID`,`RoleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_responsible`
--

LOCK TABLES `vendor_responsible` WRITE;
/*!40000 ALTER TABLE `vendor_responsible` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_responsible` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_vendoraccount`
--

DROP TABLE IF EXISTS `vendor_vendoraccount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_vendoraccount` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `VendorID` int(6) NOT NULL,
  `VendorAccountID` int(7) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `VendorID_VendorAccountID` (`VendorID`,`VendorAccountID`)
) ENGINE=InnoDB AUTO_INCREMENT=170 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_vendoraccount`
--

LOCK TABLES `vendor_vendoraccount` WRITE;
/*!40000 ALTER TABLE `vendor_vendoraccount` DISABLE KEYS */;
INSERT INTO `vendor_vendoraccount` VALUES (127,9,'2014-05-28 16:55:06',100,118);
/*!40000 ALTER TABLE `vendor_vendoraccount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendoraccount`
--

DROP TABLE IF EXISTS `vendoraccount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendoraccount` (
  `ID` int(7) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(255) NOT NULL,
  `InvoiceAddress` text NOT NULL,
  `Comments` text NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `OneCName` varchar(200) NOT NULL,
  `CompanyID` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendoraccount`
--

LOCK TABLES `vendoraccount` WRITE;
/*!40000 ALTER TABLE `vendoraccount` DISABLE KEYS */;
INSERT INTO `vendoraccount` VALUES (100,13,'2013-10-01 15:27:54','ABC-LOGISTIC OOO','GOGOLY STR.42, FLAT 9, SVERDLOVSKAYA OBLAST, EKATERIMBURG 620026 RUSSIA','',1,'',0);
/*!40000 ALTER TABLE `vendoraccount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vinvoice`
--

DROP TABLE IF EXISTS `vinvoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vinvoice` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `CompanyID` tinyint(1) NOT NULL DEFAULT 1,
  `VendorID` int(6) NOT NULL,
  `VendorAccountID` int(7) NOT NULL,
  `DocType` tinyint(2) NOT NULL DEFAULT 1,
  `ReceivedDate` date NOT NULL,
  `DocDate` date NOT NULL,
  `KPIDate` date NOT NULL,
  `DueDate` date NOT NULL,
  `Amount` decimal(9,2) NOT NULL,
  `CurrencyISO` char(3) CHARACTER SET ascii NOT NULL,
  `Comments` text NOT NULL,
  `Status` tinyint(2) NOT NULL DEFAULT 1,
  `NotOnCheckingDate` date NOT NULL,
  `CacheFirstPaymentDate` date NOT NULL,
  `CacheLastPaymentDate` date DEFAULT NULL,
  `CachePaymentsReference` text DEFAULT NULL,
  `CachePaymentsTotalAmount` decimal(11,2) DEFAULT NULL,
  `CacheOutstandingAmount` decimal(11,2) NOT NULL,
  `CopyRequestReceived` tinyint(1) NOT NULL,
  `CopyInvoiceReceived` tinyint(1) NOT NULL,
  `CopyActReceived` tinyint(1) NOT NULL,
  `CopyFakturaReceived` tinyint(1) NOT NULL,
  `OriginalRequestReceived` tinyint(1) NOT NULL,
  `OriginalInvoiceReceived` tinyint(1) NOT NULL,
  `OriginalActReceived` tinyint(1) NOT NULL,
  `OriginalFakturaReceived` tinyint(1) NOT NULL,
  `CacheLinesFileNumber` text DEFAULT NULL,
  `CacheLinesMAWBNumber` text DEFAULT NULL,
  `CacheResponsiblesUserID` text DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `VendorID` (`VendorID`,`Amount`),
  KEY `VendorAccountID` (`VendorAccountID`,`Amount`),
  KEY `DocDate` (`DocDate`)
) ENGINE=InnoDB AUTO_INCREMENT=18664 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vinvoice`
--

LOCK TABLES `vinvoice` WRITE;
/*!40000 ALTER TABLE `vinvoice` DISABLE KEYS */;
INSERT INTO `vinvoice` VALUES (100,9,'2012-01-30 12:04:43','1582111',2,3,3,1,'2012-01-26','2012-01-26','0000-00-00','2012-01-26',2745.00,'USD','',22,'0000-00-00','0000-00-00','2012-02-20','Payment 2002',2745.00,0.00,0,1,0,0,0,0,0,0,'100063','105-31863952','9');
/*!40000 ALTER TABLE `vinvoice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vinvoice_file_amount`
--

DROP TABLE IF EXISTS `vinvoice_file_amount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vinvoice_file_amount` (
  `VInvoiceID` int(11) NOT NULL,
  `FileNumber` bigint(20) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `AmountinUSD` decimal(10,2) NOT NULL,
  PRIMARY KEY (`FileNumber`,`VInvoiceID`),
  KEY `VInvoiceID` (`VInvoiceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Sum of vinvoiceline.FinalAmount per FileNumber';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vinvoice_file_amount`
--

LOCK TABLES `vinvoice_file_amount` WRITE;
/*!40000 ALTER TABLE `vinvoice_file_amount` DISABLE KEYS */;
/*!40000 ALTER TABLE `vinvoice_file_amount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vinvoice_responsible`
--

DROP TABLE IF EXISTS `vinvoice_responsible`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vinvoice_responsible` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(11) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `VInvoiceID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Status` tinyint(2) NOT NULL,
  `NotOnCheckingDate` date NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `VInvoiceID_UserID` (`VInvoiceID`,`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vinvoice_responsible`
--

LOCK TABLES `vinvoice_responsible` WRITE;
/*!40000 ALTER TABLE `vinvoice_responsible` DISABLE KEYS */;
/*!40000 ALTER TABLE `vinvoice_responsible` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vinvoiceline`
--

DROP TABLE IF EXISTS `vinvoiceline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vinvoiceline` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(5) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `VInvoiceID` int(11) NOT NULL,
  `MAWBNumber` varchar(100) NOT NULL,
  `FileNumber` bigint(20) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `UOMID` int(11) NOT NULL,
  `Quantity` decimal(11,1) NOT NULL,
  `Price` decimal(9,2) NOT NULL,
  `Amount` decimal(11,2) NOT NULL,
  `VATPercent` decimal(5,2) NOT NULL,
  `VATAmount` decimal(9,2) NOT NULL,
  `FinalAmount` decimal(11,2) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `FileNumber` (`FileNumber`),
  KEY `VendorInvoiceID` (`VInvoiceID`)
) ENGINE=InnoDB AUTO_INCREMENT=43945 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vinvoiceline`
--

LOCK TABLES `vinvoiceline` WRITE;
/*!40000 ALTER TABLE `vinvoiceline` DISABLE KEYS */;
INSERT INTO `vinvoiceline` VALUES (119,9,'2012-01-30 12:04:43',100,'105-31863952',400008,'Airfreight',2,1.0,2745.00,2745.00,-1.00,0.00,2745.00);
/*!40000 ALTER TABLE `vinvoiceline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vpayment`
--

DROP TABLE IF EXISTS `vpayment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vpayment` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(50) NOT NULL,
  `CompanyID` int(11) NOT NULL,
  `AccountID` int(11) NOT NULL,
  `VendorID` int(11) NOT NULL,
  `VendorAccountID` int(11) NOT NULL,
  `Amount` decimal(11,2) NOT NULL,
  `CacheOverpayAmount` decimal(11,2) NOT NULL,
  `CurrencyISO` char(3) NOT NULL,
  `PaymentDate` date NOT NULL,
  `OverpayCleared` tinyint(4) NOT NULL,
  `Comments` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2223 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vpayment`
--

LOCK TABLES `vpayment` WRITE;
/*!40000 ALTER TABLE `vpayment` DISABLE KEYS */;
INSERT INTO `vpayment` VALUES (100,10,'2012-09-25 13:59:51','Payment 260912 Guta',1,0,11,11,1301.20,0.00,'USD','2012-09-25',0,'');
/*!40000 ALTER TABLE `vpayment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vpayment_vinvoice`
--

DROP TABLE IF EXISTS `vpayment_vinvoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vpayment_vinvoice` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CreatedBy` int(10) NOT NULL,
  `CreatedDateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `VPaymentID` int(11) NOT NULL,
  `VInvoiceID` int(11) NOT NULL,
  `VInvoiceName` varchar(100) NOT NULL,
  `ClearingAmountInInvoiceCurrency` decimal(11,2) NOT NULL,
  `RateToInvoiceCurrency` double NOT NULL,
  `ClearingAmountInPaymentCurrency` decimal(11,2) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `VPaymentID` (`VPaymentID`),
  KEY `VInvoiceID` (`VInvoiceID`)
) ENGINE=InnoDB AUTO_INCREMENT=17921 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vpayment_vinvoice`
--

LOCK TABLES `vpayment_vinvoice` WRITE;
/*!40000 ALTER TABLE `vpayment_vinvoice` DISABLE KEYS */;
INSERT INTO `vpayment_vinvoice` VALUES (890,10,'2012-09-25 13:59:52',100,1018,'ГСЮ2-ГРГД/000002-П-756',20.00,1,0.00),(891,10,'2012-09-25 13:59:52',100,1019,'ГСЮ2-ГРГД/000002-П-757',267.40,1,0.00),(892,10,'2012-09-25 13:59:52',100,1045,'ГСЮ2-ГРГД/000002-П-759',20.00,1,0.00),(893,10,'2012-09-25 13:59:52',100,1081,'ГСЮ2-ГРГД/000002-П-760',203.35,1,0.00),(894,10,'2012-09-25 13:59:52',100,1082,'ГСЮ2-ГРГД/000002-П-761',285.65,1,0.00),(895,10,'2012-09-25 13:59:52',100,1114,'ГСЮ2-ГРГД/000002-П-763',128.31,1,0.00),(896,10,'2012-09-25 13:59:52',100,1115,'ГСЮ2-ГРГД/000002-П-762',197.53,1,0.00),(897,10,'2012-09-25 13:59:52',100,1151,'ГСЮ2-ГРГД/000002-П-764',105.00,1,0.00),(898,10,'2012-09-25 13:59:52',100,1160,'ГСЮ2-ГРГД/000002-П-765',53.95,1,0.00),(984,10,'2012-09-26 14:11:22',100,1297,'ГСЮ2-ГРГД/000002-П-758',20.00,1,0.00);
/*!40000 ALTER TABLE `vpayment_vinvoice` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-02-11 18:45:58
