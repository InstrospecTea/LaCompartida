<?php

require_once dirname(__FILE__) . '../../../conf.php';
require_once 'ApnsPHP/Autoload.php';

class APNSNotificationProvider  implements INotificationProvider {

  const CERT_TYPE_APNS = 0;
  const CERT_TYPE_AUTH = 1;
  const ENVIRONMENT_PRODUCTION = 0;
  const ENVIRONMENT_SANDBOX = 1;

  public function __construct($session, $environment) {
    $this->pushService = null;
    $this->session = $session;
    $this->environment = $environment;
  }

  public function connect() {
    $certificate = $this->chooseCeritifcate(APNSNotificationProvider::CERT_TYPE_APNS);
    $certificationAuth = $this->chooseCeritifcate(APNSNotificationProvider::CERT_TYPE_AUTH);
    if ($certificate) {
      $this->pushService = new ApnsPHP_Push($this->environment, $certificate);
      $this->pushService->setRootCertificationAuthority($certificationAuth);
      $this->pushService->connect();
      return true;
    } else {
      return false;
    }
  }

  public function addMessage($user_id, $title, $extras) {
    $tokens = $this->deviceTokensForUser($user_id);
    foreach ($tokens as $idx => $token) {
      $aps_message = new ApnsPHP_Message($token);
      $aps_message->setCustomIdentifier(sprintf("Message-TTB-%03d-%03d", $user_id, $idx));
      $aps_message->setText($title);
      if (array_key_exists("badgeNumber", $extras) && !is_null($extras["badgeNumber"])) {
        $aps_message->setBadge($extras["badgeNumber"]);
      }
      if (array_key_exists("notificationSound", $extras) && !is_null($extras["notificationSound"])) {
        $aps_message->setSound($extras["notificationSound"]);
      }
      if (array_key_exists("notificationMessage", $extras) && !is_null($extras["notificationMessage"])) {
        $aps_message->setCustomProperty('notificationMessage', $extras["notificationMessage"]);
      }
      if (array_key_exists("notificationURL", $extras) && !is_null($extras["notificationURL"])) {
        $aps_message->setCustomProperty('notificationURL', $extras["notificationURL"]);
      }
      $this->pushService->add($aps_message);
    }
  }

  public function deliver() {
    $this->pushService->send();
    $aErrorQueue = $this->pushService->getErrors();
    if (!empty($aErrorQueue)) {
      var_dump($aErrorQueue);
    }
  }

  public function disconnect() {
    $this->pushService->disconnect();
  }

  function chooseCeritifcate($type) {
    if ($type == APNSNotificationProvider::CERT_TYPE_APNS) {
      if ($this->environment = APNSNotificationProvider::ENVIRONMENT_SANDBOX) {
        return Conf::ServerDir() . '/../config/apns/apns-sandbox.pem';
      }
      if ($this->environment = APNSNotificationProvider::ENVIRONMENT_PRODUCTION) {
        return Conf::ServerDir() . '/../config/apns/server_certificates_bundle_production.pem';
      }
    }
    if ($type == APNSNotificationProvider::CERT_TYPE_AUTH) {
      return Conf::ServerDir() . '/../config/apns/entrust_root_certification_authority.pem';
    }
  }

  function deviceTokensForUser($user_id) {
    return array("ed05ca868cc8d5c8393f2eb50a32ca007c37ad777dba3e17e9a23936a2df213b");
  }

}