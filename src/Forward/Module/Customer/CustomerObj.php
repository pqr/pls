<?php
namespace Forward\Module\Customer;

use Forward\Classes\Finance;
use Forward\Field\CreditFields;
use Forward\Field\CustomerFields;
use Forward\Field\GeneralFields;
use Forward\Field\RateFields;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class CustomerObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('customer');

        $this->addFields(

            CF::id(),

            F::createString('Name', ['name' => 'Customer Name']),

            CF::active(),

            F::createText('Commodity'),

            F::createString('ContactPersonName', [
                'name' => 'Person Name',
                'nameTable' => 'E-mail Notice: Person Name'
            ]),

            F::createString('NoticeEmail', [
                'name' => 'E-mail',
                'nameTable' => 'E-mail Notice: E-mail',
                'clickable' => true
            ]),

            F::createText('Address', ['clickable' => true]),

            F::createText('Contacts', ['clickable' => true]),

            GeneralFields::companyId(),

            CreditFields::creditPeriod(),

            CreditFields::creditAmount(),

            GeneralFields::buIds(),

            F::createText('AWBInstructions', [
                'name' => 'AWB Instructions',
                'editWidth' => 'full',
                'rows' => 20
            ]),

            F::createText('OtherInstructions', [
                'name' => 'AWB Instructions',
                'editWidth' => 'full',
                'rows' => 20
            ]),

            F::createCheckbox('OnlyForResponsibles', [
                'name' => 'Доступ только для ответсвенных',
                'title' => 'Перевозки и счета этого клиента будут видны только отвественным менеджерам указанным в Responsibles'
            ]),

            CustomerFields::openInvoicesAmounts(),

            CustomerFields::overdueInvoicesAmounts(),

            CustomerFields::openPaymentsAmounts(),

            F::createCheckbox('UseDefaultAddon', ['name' => 'Use Default Addons']),
            RateFields::minAmount('AddonUSD')->setUom('USD'),
            RateFields::amountN('AddonUSD')->setUom('USD'),
            RateFields::amount45('AddonUSD')->setUom('USD'),
            RateFields::amount100('AddonUSD')->setUom('USD'),
            RateFields::amount200('AddonUSD')->setUom('USD'),
            RateFields::amount300('AddonUSD')->setUom('USD'),
            RateFields::amount500('AddonUSD')->setUom('USD'),
            RateFields::amount750('AddonUSD')->setUom('USD'),
            RateFields::amount1000('AddonUSD')->setUom('USD'),
            RateFields::amount2000('AddonUSD')->setUom('USD'),
            RateFields::minAmount('AddonEUR')->setUom('EUR'),
            RateFields::amountN('AddonEUR')->setUom('EUR'),
            RateFields::amount45('AddonEUR')->setUom('EUR'),
            RateFields::amount100('AddonEUR')->setUom('EUR'),
            RateFields::amount200('AddonEUR')->setUom('EUR'),
            RateFields::amount300('AddonEUR')->setUom('EUR'),
            RateFields::amount500('AddonEUR')->setUom('EUR'),
            RateFields::amount750('AddonEUR')->setUom('EUR'),
            RateFields::amount1000('AddonEUR')->setUom('EUR'),
            RateFields::amount2000('AddonEUR')->setUom('EUR')

        );
    }


    /**
     * Обновляет поля CacheOpenInvoicesAmountUSD/EUR/RUB и CacheOverdueInvoicesAmountUSD/EUR/RUB в том числе с группировкой
     * по компаниям и по интервалам дат Overdue
     */
    public function updateCacheOpenAndOverdueInvoicesAmount()
    {
        //Сначала очистим все кеш-данные, т.к. раньше могли быть одни валюты, а теперь получились другие
        $customerId = $this->getId();
        Db::query("DELETE FROM customer_openinvoicespercompany WHERE CustomerID = @i", $customerId);
        Db::query("DELETE FROM customer_openinvoices WHERE CustomerID = @i", $customerId);

        $query = "SELECT CustomerID, CompanyID, CurrencyISO
                      , sum(CacheOutstandingAmount) AS OpenAmount

                      , sum(IF(DueDate < CURDATE(), CacheOutstandingAmount, 0)) AS OverdueAmount

                      , sum(IF(DueDate < CURDATE() AND CURDATE() <= DATE_ADD(DueDate, INTERVAL 30 DAY),
                            CacheOutstandingAmount, 0)) AS OverdueAmount30

                      , sum(IF(CURDATE() > DATE_ADD(DueDate, INTERVAL 30 DAY) AND
                            CURDATE() <= DATE_ADD(DueDate, INTERVAL 60 DAY),
                            CacheOutstandingAmount, 0)) AS OverdueAmount3060

                      , sum(IF(CURDATE() > DATE_ADD(DueDate, INTERVAL 60 DAY) AND
                            CURDATE() <= DATE_ADD(DueDate, INTERVAL 90 DAY),
                            CacheOutstandingAmount, 0)) AS OverdueAmount6090

                      , sum(IF(CURDATE() > DATE_ADD(DueDate, INTERVAL 90 DAY) AND
                            CURDATE() <= DATE_ADD(DueDate, INTERVAL 180 DAY),
                            CacheOutstandingAmount, 0)) AS OverdueAmount90180

                      , sum(IF(CURDATE() > DATE_ADD(DueDate, INTERVAL 180 DAY), CacheOutstandingAmount, 0))
                            AS OverdueAmount180

                  FROM cinvoice
                  WHERE CustomerID = @i
                      AND Status IN (@a)
                  GROUP BY CompanyID, CurrencyISO
                  ORDER BY NULL";

        $allAmounts = Db::getAll($query, $customerId, Finance::$cinvoiceOpenStatuses);
        if (!$allAmounts) {
            return;
        }

        //Теперь пройдём по новым данным и запишем отдельно в две таблицы
        //Сначала обновим таблицу customer_openinvoicespercompany (с разбивкой на компании и валюты)
        Db::insertRows($allAmounts, 'customer_openinvoicespercompany', true);

        //Обновим разбивку по валютам без учёта компании в customer_openinvoices
        //Переформируем полученный результат по компаниям и валютам
        $amountsPerCurrency = [];
        foreach ($allAmounts as $amounts) {
            $currencyIso = $amounts['CurrencyISO'];
            //Cделаем сложение отдельных полей OpenAmount, OverdueAmount, ...
            /** @var array $amounts */
            foreach ($amounts as $key => $value) {
                if (strpos($key, 'Amount') !== false) {
                    if (!isset($amountsPerCurrency[$currencyIso][$key])) {
                        $amountsPerCurrency[$currencyIso][$key] = 0;
                    }
                    $amountsPerCurrency[$currencyIso][$key] += $value;
                } elseif ($key !== 'CompanyID') {
                    //Заполняем CustomerID и CurrencyISO
                    $amountsPerCurrency[$currencyIso][$key] = $value;
                }
            }
        }

        Db::insertRows($amountsPerCurrency, 'customer_openinvoices', true);
    }

    /**
     * Обновляет поля в таблице customer_openpayments
     */
    public function updateCacheOpenPaymentsAmount()
    {
        $customerId = $this->getId();
        //Сначала очистим все кеш-данные, т.к. раньше могли быть одни валюты, а теперь получились другие
        Db::query("DELETE FROM customer_openpayments WHERE CustomerID = @i", $customerId);

        $query = "REPLACE INTO customer_openpayments
                    (CustomerID
                    , CurrencyISO
                    , OpenPaymentsAmount)
                  SELECT @i
                    , CurrencyISO
                    , sum(CacheOverpayAmount) AS OpenPaymentsAmount
                  FROM cpayment
                  WHERE OverpayCleared = 0 AND CustomerID = @i
                  GROUP BY CurrencyISO
                  HAVING OpenPaymentsAmount != 0";

        Db::query($query, $customerId, $customerId);
    }
}
