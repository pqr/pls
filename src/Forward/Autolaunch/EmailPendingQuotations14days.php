<?php
namespace Forward\Autolaunch;

use Forward\ForwardConfig;
use Light\Config;
use Light\Db;
use Light\Mailer;
use Light\Module\Autolaunch\AutolaunchAbstract;

class EmailPendingQuotations14days extends AutolaunchAbstract
{
    protected static $_title = 'E-Mail Darft or Pending Quotations (3 days)';
    protected static $_description = 'Отправляет на E-mail уведомление о котировках находящихся 3 дня в статусе Darft или Pending. Адресаты: Customer Service с копией Sales.';

    public function run(array $params)
    {
        //Найдём все котировки, которые находятся в Pending ровно 3 дня.
        if (date('N') == 1) {
            $days = '3, 4, 5';
        } else {
            $days = '3';
        }

        $quotations = Db::getAll(
            "SELECT q.ID
                , q.Name
                , customer.Name AS CustomerName
                , DATEDIFF(NOW(), q.CreatedDateTime) AS PendingDays
                , user_cs.Email AS ResponsibleCSEmail
                , user_sales.Email AS ResponsibleSalesEmail
             FROM quotation q
                  JOIN customer ON customer.ID = q.CustomerID
                  LEFT JOIN user user_cs ON user_cs.ID = q.ResponsibleCSID
                  LEFT JOIN user user_sales ON user_sales.ID = q.ResponsibleSalesID
             WHERE (q.Status = 'pending' OR q.Status = 'draft')
                 AND DATEDIFF(NOW(), q.CreatedDateTime) IN ($days)
                 AND q.CreatedDateTime >= '2014-01-01'
             ORDER BY q.CreatedDateTime"
        );

        if (!$quotations) {
            $this->putFinalLogMsg(0, 0);
            return;
        }

        //Разобём все найденные котировки по адресатам
        $quotationsPerEmail = [];

        foreach ($quotations as $quotation) {
            $emailHash = $quotation['ResponsibleCSEmail'] . '/' . $quotation['ResponsibleSalesEmail'];
            if ($emailHash !== '/') {
                //Добавляем в очередь для отправки, только есть хотя бы одна почта
                $quotationsPerEmail[$emailHash][] = $quotation;
            }
        }

        if (!$quotationsPerEmail) {
            $this->putFinalLogMsg(0, 0);
            return;
        }

        //теперь будем отправлять письма один за одним
        $sentMails = 0;
        $errMails = 0;

        foreach ($quotationsPerEmail as $emails => $quotationsForThisMail) {

            $subject = 'Quotations in status Draft or Pending status for 3 days';
            $bodyParts = [];
            /** @var array $quotationsForThisMail */
            foreach ($quotationsForThisMail as $quotation) {
                $bodyParts[] = 'Please urgently follow the quotation <a href="' . Config::$localUrl . '/?a=Quotation&id=' . $quotation['ID'] . '">#' . $quotation['ID'] . ' (' . $quotation['Name'] . ', customer ' . $quotation['CustomerName'] . ')</a> up and convert to a respective status';
            }
            $body = $subject . "<br><br>" . implode("<br>", $bodyParts);

            $emails = explode('/', $emails);
            $cc = false;
            if ($emails[0]) {
                $to = $emails[0];
                if ($emails[1]) {
                    $cc = $emails[1];
                }
            } else {
                $to = $emails[1];
            }

            Mailer::sendMail(Config::$mailFrom, $to, $cc, $subject, $body, null, $this->_msgBuff);

        }

        $this->_msgBuff->addEmptyLine();
        $this->putFinalLogMsg($sentMails, $errMails);
    }

    private function putFinalLogMsg($sentMails, $errMails)
    {
        $msg = $sentMails . ' mails sent';
        $type = $sentMails ? 'info' : 'success';

        $this->_msgBuff->add($type, $msg);
        $this->_msgBuffShort->add($type, $msg);

        if ($errMails) {
            $errMsg = $errMails . ' filed mails';
            $this->_msgBuff->addError($errMsg);
            $this->_msgBuffShort->addError($errMsg);
        }
    }
} 