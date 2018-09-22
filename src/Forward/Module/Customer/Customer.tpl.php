<?php use Light\Template as t; ?>

<table>
    <tr>
        <td class="col-left">
            <h2>General Info</h2>
            <table class="form">
                <?= t::c('Name') ?>
                <?= t::c('Active') ?>
                <?= t::c('Commodity') ?>
                <?= t::c('Address') ?>
                <?= t::c('Contacts') ?>
                <tr class="vtop">
                    <th style="padding-top: 20px">
                        Customer Accounts
                    </th>
                    <td style="padding-top: 20px; background-color: #fff;">
                        <?= t::subtableHtml('CustomerAccounts') ?>
                    </td>
                </tr>

            </table>
        </td>

        <td class="col-middle">
            <h2>Responsibles</h2>

            <div style="float: right">
                <?= t::subtableHtml('Responsibles') ?>
            </div>
            <div style="clear: right"></div>

            <table class="form">
                <?= t::c('OnlyForResponsibles') ?>
            </table>

            <h2>Internal Info</h2>
            <table class="form">
                <?= t::c('CompanyID') ?>
                <?= t::c('CreditAmount') ?>
                <?= t::c('OpenInvoicesAmounts'); ?>
                <?= t::c('OverdueInvoicesAmounts'); ?>
                <?= t::c('OpenPaymentsAmounts'); ?>
                <?= t::c('BUIDs') ?>
            </table>

            <h2>Shipments E-mail Notice</h2>
            <table class="form">
                <?= t::c('ContactPersonName') ?>
                <?= t::c('NoticeEmail') ?>
            </table>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td>

            <ul class="tabs" data-active="<?= \Light\User::getParam('Customer:tabs:') ?>">
                <li><a href="#tabs-contacts">Contacts
                        (<?= t::subtableCount('Contacts'); ?>)</a></li>
                <li><a
                            href="#tabs-originsagents">Origins & Agents (<?= t::subtableCount('Agents'); ?>)</a></li>
                <li><a
                            href="#tabs-instructions">Instructions</a></li>
                <li><a href="#tabs-quotations">Quotations
                        (<?= t::subtableCount('Quotations'); ?>)</a></li>
                <li><a
                            href="#tabs-standartrates">Standart Rates (<?= t::subtableCount('StandartRates'); ?>)</a>
                </li>
                <li><a href="#tabs-addon">Addons
                        (<?= t::value('UseDefaultAddon') ? 'Default' : '+' ?>)</a></li>

                <li><a href="#tabs-consignee">Consignee (<?= t::subtableCount('Consignee'); ?>)</a></li>
            </ul>


            <div id="tabs-contacts">
                <?= t::subtableHtml('Contacts'); ?>
            </div>
            <div id="tabs-originsagents">
                <?= t::subtableHtml('Agents'); ?>
            </div>
            <div id="tabs-instructions">
                <h2>AWB Instructions</h2>

                <div class="instructions-div">
                    <?= t::html('AWBInstructions') ?>
                </div>

                <h2>Other Instructions</h2>

                <div class="instructions-div">
                    <?= t::html('OtherInstructions') ?>
                </div>
            </div>
            <div id="tabs-quotations">
                <?php if (t::_state() != \Light\ModuleBase\ModuleAbstract::STATE_ADDNEW): ?>
                    <div class="buttonsbar">
                        <a href="?a=Quotation&filterValues=<?=
                        htmlspecialchars(
                            json_encode(['CustomerID' => t::_id()])
                        ) ?>"><?= \Light\Img::iconSearch(); ?> View in Quotations module</a>

                        <a href="?a=Quotation&addnew=1&CustomerID=<?= t::_id() ?>"><?= \Light\Img::iconPlus(); ?>
                            New
                            Quotation</a>
                    </div>

                    <?= t::subtableHtml('Quotations') ?>
                <?php endif; ?>
            </div>
            <div id="tabs-standartrates">
                <?php if (t::_state() != \Light\ModuleBase\ModuleAbstract::STATE_ADDNEW): ?>
                    <div class="buttonsbar">
                        <a href="?a=Customer&id=<?= t::_id() ?>&func=StandartRateDialog&action=new#ajax"><?= \Light\Img::iconPlus(); ?>
                            New Standart Rate</a>

                        <a href="#" class="standartrates__exporttoexcel"><?= \Light\Img::iconExcel() ?> Export to Excel</a>
                    </div>



                    <?= t::subtableHtml('StandartRates') ?>
                <?php endif; ?>
            </div>
            <div id="tabs-addon">
                <?php if (\Light\User::hasReadAccess('DefaultAddons')) {
                    print \Light\Link::create('DefaultAddons', '<small style="float: right">View default addons</small>')->blank()->getHtml();
                } ?>
                <table class="form">
                    <?= t::c('UseDefaultAddon'); ?>
                </table>
                <table class="<?= t::value('UseDefaultAddon') ? 'display-none' : '' ?>" id="addons-table">
                    <tr>
                        <td>
                            <table class="form">
                                <?= t::c('AddonUSDMinAmount'); ?>
                                <?= t::c('AddonUSDAmountN'); ?>
                                <?= t::c('AddonUSDAmount45'); ?>
                                <?= t::c('AddonUSDAmount100'); ?>
                                <?= t::c('AddonUSDAmount200'); ?>
                                <?= t::c('AddonUSDAmount300'); ?>
                                <?= t::c('AddonUSDAmount500'); ?>
                                <?= t::c('AddonUSDAmount750'); ?>
                                <?= t::c('AddonUSDAmount1000'); ?>
                                <?= t::c('AddonUSDAmount2000'); ?>
                            </table>
                        </td>
                        <td>
                            <table class="form">
                                <?= t::c('AddonEURMinAmount'); ?>
                                <?= t::c('AddonEURAmountN'); ?>
                                <?= t::c('AddonEURAmount45'); ?>
                                <?= t::c('AddonEURAmount100'); ?>
                                <?= t::c('AddonEURAmount200'); ?>
                                <?= t::c('AddonEURAmount300'); ?>
                                <?= t::c('AddonEURAmount500'); ?>
                                <?= t::c('AddonEURAmount750'); ?>
                                <?= t::c('AddonEURAmount1000'); ?>
                                <?= t::c('AddonEURAmount2000'); ?>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="tabs-consignee">
                <?= t::subtableHtml('Consignee') ?>
            </div>

        </td>
    </tr>
</table>
