<?php

$I = new ApiTester($scenario);
$I->wantTo('login a user via API');
# $I->amHttpAuthenticated('service_user', '123456');
$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
$I->sendPOST('/login', array('user' => '99511620', 'password' => 'Etropos2015'));

$I->seeResponseCodeIs(200);
//$I->seeResponseIsJson();
//$I->seeResponseContains('{"result":"ok"}');
