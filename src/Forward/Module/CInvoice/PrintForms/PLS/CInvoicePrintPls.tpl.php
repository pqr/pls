<?php

use Light\Template as t; ?>
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
        font-size: 11px;
        font-family: Century Gothic, Verdana, Arial;
        letter-spacing: .1em;
    }

    #headfields-table td.bordered {
        padding: 4px;
        border: 1px solid #000000;
    }

    table {
        width: 100%;
    }
    table th {
        text-align: left;
    }
    .nobr {
        white-space: nowrap;
    }
</style>
<table style="width: auto">
    <tr>
        <td>
            <table>
                <tr>
                    <td width="120"><img id="logo" src="i/login/loginlogo.png" alt="Logo" width="110" height="110"></td>
                    <td width="300">
                        <h1 style="font-family: Verdana, Arial; font-size: 16px; font-weight: bold; padding: 2px;">
                            <?= htmlspecialchars($OurCompanyName_raw) ?>
                        </h1>

                        <div style="padding: 2px;font-family: Verdana, Arial;font-size: 11px;">
                            <?= nl2br(htmlspecialchars($OurCompanyAddress_raw)) ?>
                        </div>
                    </td>
                    <td width="180"
                        style="font-size: 40px; text-align: right; font-family: Century Gothic, Verdana, Arial"><?= strtoupper(
                            t::text('DocType')
                        ) ?>
                    </td>
                </tr>
            </table>

            <table>
                <tr valign="bottom">
                    <td style="padding-right: 5px; width: 35%; font-family: Verdana, Arial; font-size: 9px;">
                        <strong>Beneficiary Bank:</strong>
                    </td>

                    <td style="padding: 0 5px 0 5px; width: 35%; font-family: Verdana, Arial; font-size: 9px;">
                        <strong>Correspondent Bank:</strong>
                    </td>

                    <td style="font-family: Verdana, Arial; font-size: 14px; text-align: right;">
                        Date: <?= t::html('DocDate') ?>
                        <br><strong><?= strtoupper(t::text('DocType')) ?> # <?= t::html('Name') ?></strong>
                    </td>
                </tr>
                <tr valign="top">
                    <td style="padding-right: 5px; width: 35%; font-family: Verdana, Arial; font-size: 9px;">
                        <?php
                        $bankLines = explode("\n", $OurCompanyBeneficiaryBank_raw);
                        $bankLinesHtml = array_map(
                            function ($line) {
                                return '<div class="nobr"><nobr>' . \htmlspecialchars($line) . '</nobr></div>';
                            },
                            $bankLines
                        );
                        ?>

                        <p><?= implode('', $bankLinesHtml) ?></p>
                    </td>

                    <td style="padding: 0 5px 0 5px; width: 35%; font-family: Verdana, Arial; font-size: 9px;">
                        <?php
                        $corrBankLines = explode("\n", $OurCompanyCorrespondentBank_raw);
                        $corrBankLinesHtml = array_map(
                            function ($line) {
                                return '<div class="nobr"><nobr>' . \htmlspecialchars($line) . '</nobr></div>';
                            },
                            $corrBankLines
                        );
                        ?>
                        <p><?= implode('', $corrBankLinesHtml) ?></p>
                    </td>

                    <td style="font-family: Century Gothic, Verdana, Arial; font-size: 11px;">
                            To:
                            <?= t::html('InvoiceTo') ?>
                    </td>
                </tr>
            </table>

            <?php
            include __DIR__ . '/../InvoicePrintLayout.tpl.php' ?>

            <p style="font-weight: bold; text-align: right">Total:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= t::html(
                    'Amount'
                ) ?>&nbsp;&nbsp;<?= t::html('CurrencyISO') ?></p>

            <p style="font-weight: bold; font-size: 11px; text-align: center; font-family: Verdana; margin-top: 40px;">
                Thank you for your business!</p>

            <?php
            if ($WithStamp): ?>
                <table align="center" style="width: 80%">
                    <tr>
                        <td align="right"><img id="stamp" src="i/PLSStamp.jpg"
                                               width="210" height="210"></td>
                    </tr>
                </table>
            <?php
            endif; ?>
        </td>
    </tr>
</table>

