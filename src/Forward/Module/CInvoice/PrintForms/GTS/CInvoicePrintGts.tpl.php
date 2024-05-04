<?php use Light\Template as t; ?>
<style>
    html, body {
        background-color: #FFFFFF;
        font-family: Verdana, serif;
        font-size: 12px;
        margin: 0 auto !important;
        padding: 0 !important;
    }

    #headfields-table {
        border-collapse: collapse;
        width: 100%;
    }

    #headfields-table td {
        padding: 8px 4px 4px 4px;
        text-align: center;
        font-weight: bold;
    }

    #headfields-table td.bordered {
        padding: 4px;
        border: 1px solid #000000;
        font-weight: normal;
    }
</style>
<table style="width: auto">
    <tr>
        <td>
            <table>
                <tr valign="top">
                    <td width="75%">
                        <h1 style="font-family: Century Gothic, Verdana, Arial; font-size: 26px; font-weight: normal;">
                            <?= htmlspecialchars((string) $OurCompanyName_raw) ?>
                        </h1>
                        <span style="font-size: 13px;"><?= nl2br(htmlspecialchars((string) $OurCompanyAddress_raw)) ?></span>
                    </td>
                    <td width="25%"
                        style="font-size: 40px; text-align: right; font-family: Century Gothic, Verdana, Arial"><?= strtoupper((string) t::text('DocType')) ?>
                    </td>
                </tr>
            </table>

            <table width="100%">
                <tr>
                    <td>
                        <div style="text-align: right;">
                            Date: <?= t::html('DocDate') ?><br>
                            <?= strtoupper((string) t::text('DocType')) ?> # <?= t::html('Name') ?>
                        </div>


                        <table align="right" style="width: auto;">
                            <tr valign="top">
                                <td style="padding-right: 25px;">To:</td>
                                <td style="text-align: left;"><?= t::html('InvoiceTo') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <?php include __DIR__.'/../InvoicePrintLayout.tpl.php' ?>

            <p style="font-weight: bold; text-align: right">Total:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?= t::html('Amount') ?>&nbsp;&nbsp;<?= t::html('CurrencyISO') ?>
            </p>

            <p style="font-weight: bold; font-size: 11px; text-align: center; margin-top: 40px;">Thank you for your
                business!</p>

            <table style="width: auto; margin: auto;">
                <tr valign="top">
                    <td style="padding-right: 20px;">
                        <strong>Beneficiary Bank:</strong>

                        <p><?= nl2br(htmlspecialchars((string) $OurCompanyBeneficiaryBank_raw)) ?></p>
                    </td>
                    <td>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                    </td>

                    <td style="padding-left: 20px;">
                        <strong>Correspondent Bank:</strong>

                        <p><?= nl2br(htmlspecialchars((string) $OurCompanyCorrespondentBank_raw)) ?></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
