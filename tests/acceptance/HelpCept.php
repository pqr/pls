<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test Home');
$I->amOnPage('?a=Help');
$I->canSee('Полезная информация');
$I->cantSeeXdebugError();
