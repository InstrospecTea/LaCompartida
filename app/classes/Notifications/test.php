<?php
require_once dirname(__FILE__) . '/../conf.php';
$sesion = new Sesion(null, true);

error_reporting(-1);

$options = array(
  "provider" =>  NotificationService::PROVIDER_APNS,
  "environment" => NotificationService::ENVIRONMENT_SANDBOX
);

$notificationService = new NotificationService($sesion, $options);
$notificationService->addMessage("", "Time Billing informa nuevamente", array(
  "notificationURL" => "http://www.thetimebilling.com",
  "notificationMessage" => "Revisa nuestro nuevo sitio web !"
  ));
$notificationService->deliver();
