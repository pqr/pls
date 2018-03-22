ALTER TABLE shipment CHANGE IncotermsLocationName IncotermsLocation VARCHAR(100) NOT NULL;
ALTER TABLE shipment DROP Incoterms_old;

ALTER TABLE airfreightsurcharge CHANGE IncotermsLocationName IncotermsLocation VARCHAR(100) NOT NULL;


ALTER TABLE customer_standartrates CHANGE IncotermsLocation IncotermsLocation VARCHAR(100) NOT NULL;


ALTER TABLE quotation CHANGE IncotermsLocationName IncotermsLocation VARCHAR(100) NOT NULL;

ALTER TABLE cinvoice CHANGE IncotermsLocationName IncotermsLocation VARCHAR(100) NOT NULL;
