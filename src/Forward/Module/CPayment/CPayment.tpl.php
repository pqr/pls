<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
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
    </tr>
</table>