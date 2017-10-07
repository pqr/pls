<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('test login page');

$I->amOnPage('?a=Login');
$I->see('Login:');
$I->see('Password:');
