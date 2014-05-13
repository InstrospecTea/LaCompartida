<?php
use \WebGuy;

class LoginCest
{

    public function _before()
    {
    }

    public function _after()
    {
    }

    // tests
    public function successfulLoginTest(WebGuy $I) {
    	$I->wantTo('check that login works');
		$I->amOnPage(LoginPage::$URL);
		$I->fillField(LoginPage::$rutField, '99511620');
		$I->fillField(LoginPage::$dvField, '0');
		$I->fillField(LoginPage::$passwordField, 'admin.asdwsx');
		$I->click(LoginPage::$submitButton);
		$I->see('Admin Lemontech LEM');
    }

    public function unsuccessfulLoginTest(WebGuy $I){
    	$I->wantTo('check that login fails with incorrect login credentials');
		$I->amOnPage(LoginPage::$URL);
		$I->fillField(LoginPage::$rutField, '99511620');
		$I->fillField(LoginPage::$dvField, '0');
		$I->fillField(LoginPage::$passwordField, 'admin');
		$I->click(LoginPage::$submitButton);
		$I->see('RUT o password inválidos');
    }

}