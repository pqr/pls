ALTER TABLE customeraccount ADD CreditPeriod INT DEFAULT 0 NOT NULL;

UPDATE customeraccount ca, customer_customeraccount cca, customer c 
SET ca.CreditPeriod = c.CreditPeriod
WHERE ca.ID = cca.CustomerAccountID
    AND cca.CustomerID = c.ID
AND c.CreditPeriod != 0;