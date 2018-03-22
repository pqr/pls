<table style="margin-top: 20px;">
    <tbody>
    <tr>
        <td class="cs-income-title-td"><h2 class="selling">Selling</h2></td>
        <td class="cs-expenses-title-td">
            <h2 class="buying">Buying
                <small>
                    <a href="#" id="search-airfreightrate-link" class="display-none"><span class="glyphicon glyphicon-search"></span>
                        Search Airfreight Rate</a>
                    <a href="#" id="copy-to-selling-link" class="display-none"><span class="glyphicon glyphicon-arrow-left"></span>
                        Copy to Selling</a>
                </small>
            </h2>
        </td>
    </tr>
    <tr style="vertical-align: top">
        <td class="cs-income-td cs-td type-income">
            <?= $Income ?>
        </td>
        <td class="cs-expenses-td cs-td type-expenses">
            <?= $Expenses ?>
        </td>
    </tr>
    <tr style="vertical-align: top">
        <td colspan="2" style="padding-bottom: 20px;">

            <div class="cs-egp-div <?= $EGP_value > 0 ? 'good' : 'bad'; ?>">Estimated Gross Profit: <span
                    class="cs-egp-value"><?= $EGP ?></span> USD
            </div>

            <div class="formactions cs-save-div display-none">
                <input type="button" class="waves-effect btn btn-save bgcolor-btn-primary" value="Save Cost-sheet">
                <a href="#" class="cs-cancel-link inner">Cancel</a>
            </div>

        </td>
    </tr>

    <tr>
        <td class="cs-income-title-td"><h2 class="selling">Customer Invoices</h2></td>
        <td class="cs-expenses-title-td"><h2 class="buying">Vendor Invoices</h2></td>
    </tr>
    <tr style="vertical-align: top">
        <td class="cs-td type-income">
            <?= $CInvoices ?>
        </td>
        <td class="cs-td type-expenses">
            <?= $VInvoices ?>
        </td>
    </tr>

    <tr style="vertical-align: top">
        <td colspan="2" style="padding-bottom: 20px;">
            <div class="cs-gp-div <?= $GP_value > 0 ? 'good' : 'bad'; ?>">Gross Profit: <span
                    class="cs-gp-value"><?= $GP ?></span> USD
            </div>
            <div
                class="cs-deviation-div <?= $Deviation_value < 0 ? 'bad' : '' ?>">
                Deviation: <span class="cs-deviation-value"><?= $Deviation ?></span> USD
            </div>
        </td>
    </tr>
    </tbody>
</table>
