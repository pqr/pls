DELETE FROM airfreightrate
WHERE ID != 100;
DELETE FROM airfreightsurcharge
WHERE ID != 10;
DELETE FROM airline
WHERE ID != 100;

TRUNCATE cache_notemptyfilesfolder;
TRUNCATE ccontact;

DELETE FROM cinvoice
WHERE ID NOT IN (8508, 8528, 9104, 9093);
TRUNCATE cinvoice_file_amount;
DELETE FROM cinvoiceline
WHERE CInvoiceID NOT IN (8508, 8528, 9104, 9093);

DELETE FROM cpayment
WHERE ID != 200;
DELETE FROM cpayment_cinvoice
WHERE CPaymentID != 200;

DELETE FROM csexpenses
WHERE ShipmentID NOT IN (8458, 7773);
TRUNCATE cshistory;
DELETE FROM csincome
WHERE ShipmentID NOT IN (8458, 7773);

DELETE FROM currencyrate
WHERE ValidDate != '2017-01-18';
DELETE FROM currencyrate_citadele
WHERE ID != 100;

DELETE FROM customer
WHERE ID != 100;
DELETE FROM customer_customeraccount
WHERE CustomerID != 100;

TRUNCATE customer_openinvoices;
TRUNCATE customer_openinvoicespercompany;
TRUNCATE customer_openpayments;

DELETE FROM customer_responsible
WHERE CustomerID != 100;
DELETE FROM customer_standartrates
WHERE CustomerID != 100;

TRUNCATE customer_standratrates_buyingsurcharges;
TRUNCATE customer_standratrates_sellingsurcharges;

DELETE FROM customeraccount
WHERE ID != 100;

TRUNCATE customeragent;
TRUNCATE customerconsignee;

TRUNCATE dblog;
TRUNCATE dblog_table;
TRUNCATE dblog_module;
TRUNCATE dblog_field;
TRUNCATE dblog_text;
TRUNCATE dblog_trace;
TRUNCATE deleted;
TRUNCATE deleted_text;
TRUNCATE deleted_trace;

TRUNCATE importoneclog;

DELETE FROM list_chargedescription
WHERE ID != 2;
TRUNCATE list_concatposition;
TRUNCATE list_consignee;
TRUNCATE list_destination;
TRUNCATE list_placeofreceipt;
TRUNCATE list_shipper;
TRUNCATE list_vessel;

DELETE FROM location
WHERE ID != 2;

TRUNCATE log_autolaunch;
TRUNCATE log_email;
TRUNCATE log_telegram;

TRUNCATE metric;

-- сохраняем историю всех уже применённых миграций, но при этом очищем подробные логи для умеьшения веса тестовой базы
UPDATE migration
SET Report = '';

TRUNCATE modulesession;

DELETE FROM oceancarrier
WHERE ID != 1;

TRUNCATE ourcompanybank;

TRUNCATE pdfhash;
TRUNCATE phpsession;
TRUNCATE phpsessionflash;

DELETE FROM port
WHERE ID != 36;
TRUNCATE portterminal;

TRUNCATE queue_email;

DELETE FROM quotation
WHERE ID != 100;
DELETE FROM quotationcargo
WHERE QuotationID != 100;
DELETE FROM quotationexpenses
WHERE QuotationID != 100;
DELETE FROM quotationincome
WHERE QuotationID != 100;
TRUNCATE reportaccess;

DELETE FROM shipment
WHERE ID NOT IN (8458, 7773);
TRUNCATE shipmentcriterion;
TRUNCATE shipmentshipper;


TRUNCATE user_filter;
TRUNCATE user_filter_history;
TRUNCATE user_filter_log;
TRUNCATE user_filter_saved;
TRUNCATE user_filter_settings;
TRUNCATE user_param;
TRUNCATE user_tablelayout;
TRUNCATE user_tableparam;
-- TRUNCATE user_telegramid;
TRUNCATE user_visit;

-- UPDATE user SET Password = '', PasswordOld = '' WHERE Login != 'Admin';
DELETE FROM user
WHERE Login != 'admin';
UPDATE user
SET Password = 'admin', PasswordPlain = ''
WHERE Login = 'admin';


TRUNCATE vcontact;
DELETE FROM vendor
WHERE ID != 100;
TRUNCATE vendor_responsible;
DELETE FROM vendor_vendoraccount
WHERE VendorID != 100;
DELETE FROM vendoraccount
WHERE ID != 100;
DELETE FROM vinvoice
WHERE ID != 100;
TRUNCATE vinvoice_file_amount;
TRUNCATE vinvoice_responsible;
DELETE FROM vinvoiceline
WHERE VInvoiceID != 100;
DELETE FROM vpayment
WHERE ID != 100;
DELETE FROM vpayment_vinvoice
WHERE VPaymentID != 100;
