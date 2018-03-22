ALTER TABLE importoneclog RENAME TO log_onecimport;

ALTER TABLE cpayment_cinvoice ADD FromOneC TINYINT(1) DEFAULT 0 NOT NULL;
