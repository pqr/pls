<?php use Light\Template as t; ?>
<table>
    <tr>
        <td class="col-left">
            <table class="form">
                <?= t::h3('Name') ?>
                <?= t::c('CompanyID') ?>
                <?= t::c('CustomerID') ?>
                <?= t::c('CustomerAccountID') ?>
                <?= t::c('PaymentDate') ?>
                <?= t::amountWithCurrency('Amount') ?>
                <?php if (t::_state() !== \Light\ModuleBase\ModuleAbstract::STATE_ADDNEW): ?>
                    <?php if (t::value('CacheOverpayAmount') != 0): ?>
                        <?= t::c('CacheOverpayAmount') ?>
                        <?= t::c('OverpayCleared') ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?= t::c('Comments') ?>
            </table>

            <?= t::subtable('CInvoices') ?>

        </td>
        <td class="col-right">
            <?php if (\Forward\Classes\Constant::isOneCCompany((int)t::value('CompanyID'))): ?>
                <table class="form">
                    <?= t::c("File3") ?>
                    <?= t::c("File4") ?>
                    <?= t::c('PrintedDate') ?>
                </table>
            <?php endif; ?>
        </td>
    </tr>
</table>