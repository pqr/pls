<?php use Light\Template as t; ?>
<form action="?a=EmailNoticeSettings&p=submit#ajax" method="post">
    <table>
        <tr>
            <td>
                <table class="form">
                    <?= t::c('EmailNoticeSubject') ?>
                    <?= t::c('EmailNoticeBody') ?>
                </table>
            </td>
            <td>
                <p>
                    Для подстановки в тему или тело письма можно использовать специальные обозначения в фигруных
                    скобках:
                </p>
                <ul>
                    <li>{TrackTraceLink}</li>
                    <li>{ContactPersonName}</li>
                    <li>{MAWBNumber}</li>
                    <li>{MAWBDate}</li>
                    <li>{ResponsibleSales}</li>
                    <li>{ResponsibleCS}</li>
                    <li>{Pieces}</li>
                    <li>{ChargeableWeight}</li>
                    <li>{GrossWeight}</li>
                    <li>{Volume}</li>
                    <li>{NatureOfGoods}</li>
                    <li>{AirlineAlphaCode}</li>
                    <li>{PlaceOfReceipt}</li>
                    <li>{DepartureIATA}</li>
                    <li>{ArrivalIATA}</li>
                </ul>
            </td>
        </tr>
    </table>

    <div class="formactions">
        <button class="waves-effect waves-light btn bgcolor-btn-primary btn-save" type="submit">Save E-Mail Notice Settings</button>
    </div>
</form>


