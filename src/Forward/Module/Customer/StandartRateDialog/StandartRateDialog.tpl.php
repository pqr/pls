<?php use Light\Template as t; ?>
<?php if ($_GET['action'] == 'new' || $_GET['action'] == 'edit'): ?>
<form action="<?= $formActionUrl ?>#ajax" method="post" name="StandartRateDialog-form"
      data-fields="<?= htmlspecialchars(json_encode($fields)) ?>">
    <?php endif; ?>

    <table width="100%">
        <tr>
            <td class="col-left">
                <table class="form">
                    <?= t::h3('Name') ?>
                    <?= t::c('ResponsibleSalesID') ?>
                    <?= t::c('ResponsibleCSID') ?>
                    <?= t::c('NatureOfGoods') ?>
                    <?php include LIGHT_DIR_PROJECT . '/templates/DepartureIataLocation.tpl.php' ?>
                    <?php include LIGHT_DIR_PROJECT . '/templates/ArrivalIataLocation.tpl.php' ?>
                    <?= t::datesFromUntil('Valid') ?>
                </table>
            </td>
        </tr>
        <tr>
            <td width="50%">
                <h2 class="selling">Selling
                    <small>
                        <a href="#" class="apply-addon"><span class="glyphicon glyphicon-plus"></span> Apply Addon</a>
                    </small>
                </h2>
                <table class="form">
                    <?= t::c('SellingMinAmount'); ?>
                    <?= t::c('SellingAmountN'); ?>
                    <?= t::c('SellingAmount45'); ?>
                    <?= t::c('SellingAmount100'); ?>
                    <?= t::c('SellingAmount200'); ?>
                    <?= t::c('SellingAmount300'); ?>
                    <?= t::c('SellingAmount500'); ?>
                    <?= t::c('SellingAmount750'); ?>
                    <?= t::c('SellingAmount1000'); ?>
                    <?= t::c('SellingAmount2000'); ?>
                    <?= t::c('SellingFSCAmount'); ?>
                    <?= t::c('SellingSSCAmount'); ?>
                    <?= t::c('SellingCurrencyISO'); ?>
                </table>

                <div style="margin: 5px 0">
                    <strong class="selling">Selling Locals:</strong>
                    <?= $SellingSurchargesHtml ?>
                </div>

                <table class="form">
                    <?= t::c('CommentsCustomer') ?>
                    <?php include LIGHT_DIR_PROJECT . '/templates/AirlineAlphaCode.tpl.php'; ?>
                    <?php include LIGHT_DIR_PROJECT . '/templates/Incoterms.tpl.php'; ?>
                    <?= t::c('TransitTime') ?>
                </table>
            </td>

            <td width="50%">
                <h2 class="buying">Buying
                    <small>
                        <a href="#" class="search-airfreightrate-link"><span class="glyphicon glyphicon-search"></span>
                            Search Airfreight Rate</a>
                        <a href="#" class="copy-to-selling-link"><span class="glyphicon glyphicon-arrow-left"></span>
                            Copy to Selling</a>
                    </small>
                </h2>
                <table class="form">
                    <?= t::c('BuyingMinAmount'); ?>
                    <?= t::c('BuyingAmountN'); ?>
                    <?= t::c('BuyingAmount45'); ?>
                    <?= t::c('BuyingAmount100'); ?>
                    <?= t::c('BuyingAmount200'); ?>
                    <?= t::c('BuyingAmount300'); ?>
                    <?= t::c('BuyingAmount500'); ?>
                    <?= t::c('BuyingAmount750'); ?>
                    <?= t::c('BuyingAmount1000'); ?>
                    <?= t::c('BuyingAmount2000'); ?>
                    <?= t::c('BuyingFSCAmount'); ?>
                    <?= t::c('BuyingSSCAmount'); ?>
                    <?= t::c('BuyingCurrencyISO'); ?>
                </table>

                <div style="margin: 5px 0">
                    <strong class="buying">Buying Locals:</strong>
                    <?= $BuyingSurchargesHtml ?>
                </div>

                <table class="form">
                    <?= t::c('CommentsInternal') ?>
                    <?= t::c('VendorID') ?>
                </table>
            </td>
        </tr>
    </table>

    <?php if ($_GET['action'] == 'new' || $_GET['action'] == 'edit'): ?>
</form>
<?php endif ?>
