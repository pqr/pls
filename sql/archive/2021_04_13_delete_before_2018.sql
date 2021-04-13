delete from shipment where CreatedDateTime < '2018-01-01';
delete from csexpenses WHERE NOT EXISTS(SELECT 1 FROM shipment WHERE shipment.ID = csexpenses.ShipmentID);
delete from csincome WHERE NOT EXISTS(SELECT 1 FROM shipment WHERE shipment.ID = csincome.ShipmentID);
delete from cshistory WHERE NOT EXISTS(SELECT 1 FROM shipment WHERE shipment.ID = cshistory.ShipmentID);
delete from shipmentcriterion WHERE NOT EXISTS(SELECT 1 FROM shipment WHERE shipment.ID = shipmentcriterion.ShipmentID);
delete from shipmentshipper WHERE NOT EXISTS(SELECT 1 FROM shipment WHERE shipment.ID = shipmentshipper.ShipmentID);

delete from cinvoice where CreatedDateTime < '2018-01-01';
delete from cinvoiceline WHERE NOT EXISTS(SELECT 1 FROM cinvoice WHERE cinvoice.ID = cinvoiceline.CInvoiceID);
delete from cinvoice_file_amount WHERE NOT EXISTS(SELECT 1 FROM cinvoice WHERE cinvoice.ID = cinvoice_file_amount.CInvoiceID);

delete from vinvoice where CreatedDateTime < '2018-01-01';
delete from vinvoiceline WHERE NOT EXISTS(SELECT 1 FROM vinvoice WHERE vinvoice.ID = vinvoiceline.VInvoiceID);
delete from vinvoice_file_amount WHERE NOT EXISTS(SELECT 1 FROM vinvoice WHERE vinvoice.ID = vinvoice_file_amount.VInvoiceID);

delete from cpayment_cinvoice where NOT EXISTS(SELECT 1 FROM cinvoice WHERE cinvoice.ID = cpayment_cinvoice.CInvoiceID);
delete from cpayment WHERE NOT EXISTS(SELECT 1 FROM cpayment_cinvoice WHERE cpayment.ID = cpayment_cinvoice.CPaymentID);

delete from vpayment_vinvoice where NOT EXISTS(SELECT 1 FROM vinvoice WHERE vinvoice.ID = vpayment_vinvoice.VInvoiceID);
delete from vpayment WHERE NOT EXISTS(SELECT 1 FROM vpayment_vinvoice WHERE vpayment.ID = vpayment_vinvoice.VPaymentID);

delete from quotation where CreatedDateTime < '2018-01-01';
delete from quotationcargo where NOT EXISTS(SELECT 1 FROM quotation WHERE quotation.ID = quotationcargo.QuotationID);
delete from quotationexpenses where NOT EXISTS(SELECT 1 FROM quotation WHERE quotation.ID = quotationexpenses.QuotationID);
delete from quotationincome where NOT EXISTS(SELECT 1 FROM quotation WHERE quotation.ID = quotationincome.QuotationID);


delete from currencyrate where CreatedDateTime < '2018-01-01';
delete from currencyrate_citadele where CreatedDateTime < '2018-01-01';


delete from dblog where CreatedDateTime < '2018-01-01';
delete from dblog_text where not exists(select 1 from dblog where dblog.ID = dblog_text.dblogID);
delete from dblog_trace where CreatedDateTime < '2018-01-01';

delete from deleted where CreatedDateTime < '2018-01-01';
delete from deleted_text where not exists(select 1 from deleted where deleted.ID = deleted_text.deletedID);
delete from deleted_trace where CreatedDateTime < '2018-01-01';

delete from log_autolaunch where CreatedDateTime < '2018-01-01';
delete from log_autolaunch_text where not exists(select 1 from log_autolaunch where log_autolaunch.ID = log_autolaunch_text.ID);
delete from log_email where CreatedDateTime < '2018-01-01';
delete from log_email_text where not exists(select 1 from log_email where log_email.ID = log_email_text.ID);

delete from log_onecimport where CreatedDateTime < '2018-01-01';
delete from metric where CreatedDateTime < '2018-01-01';

delete from customer_responsible where UserID IN (SELECT ID FROM user WHERE Active = 0);
