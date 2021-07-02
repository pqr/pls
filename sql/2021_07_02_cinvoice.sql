alter table cinvoice modify Amount decimal(15,2) not null;

alter table cinvoice
    add AmountInUSD DECIMAL(15,2) default 0.00 not null;

update cinvoice set cinvoice.AmountInUSD = Amount WHERE CurrencyISO = 'USD';
