<?php use Light\Template as t; ?>

<form action="?a=DefaultAddons&p=save#ajax" style="width:500px" method="post">
    <?= t::csrfHidden() ?>
    <table>
        <tr>
            <td class="col-left">
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
            <td class="col-middle">
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

    <div class="formactions">
        <button type="submit" class="waves-effect btn bgcolor-btn-primary btn-save" data-htmldisabled="Saving...">Save Default Addons
        </button>
    </div>
</form>
