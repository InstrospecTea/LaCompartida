<?php

require_once dirname(__FILE__) . '../../../conf.php';
require_once Conf::ServerDir() . '/classes/Log.php';
require_once 'ApnsPHP/Autoload.php';

class ApnsNotificationLog implements ApnsPHP_Log_Interface
{
  /**
   * Logs a message.
   *
   * @param  $sMessage @type string The message.
   */
  public function log($sMessage) {
    Log::write(trim($sMessage), "APNSNotificationProvider");
  }
}

class APNSNotificationProvider  implements INotificationProvider {

  const CERT_TYPE_APNS = 0;
  const CERT_TYPE_AUTH = 1;
  const ENVIRONMENT_PRODUCTION = 0;
  const ENVIRONMENT_SANDBOX = 1;

  const ERROR_INVALID_TOKEN = 8;

  public function __construct($session, $environment) {
    $this->pushService = null;
    $this->session = $session;
    $this->environment = $environment;
    $this->logger = new ApnsNotificationLog();
  }

  public function connect() {
    $certificate = $this->chooseCeritifcate(APNSNotificationProvider::CERT_TYPE_APNS);
    $certificationAuth = $this->chooseCeritifcate(APNSNotificationProvider::CERT_TYPE_AUTH);
    if ($certificate) {
      $this->pushService = new ApnsPHP_Push($this->environment, $certificate);
      $this->pushService->setLogger($this->logger);
      $this->pushService->setRootCertificationAuthority($certificationAuth);
      $this->pushService->connect();
      $this->tokens = array();
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
      if (array_key_exists("badgeNumber", $extras) && !is_null($extras["badgeNumber"])) {
        $aps_message->setBadge($extras["badgeNumber"]);
      }
      if (array_key_exists("notificationSound", $extras) && !is_null($extras["notificationSound"])) {
        $aps_message->setSound($extras["notificationSound"]);
      }
      if (array_key_exists("notificationMessage", $extras) && !is_null($extras["notificationMessage"])) {
        $aps_message->setCustomProperty('notificationMessage', $extras["notificationMessage"]);
        $title .= ': ' . $extras["notificationMessage"];
      }
      if (array_key_exists("notificationTitle", $extras) && !is_null($extras["notificationTitle"])) {
        $aps_message->setCustomProperty('notificationTitle', $extras["notificationTitle"]);
      }
      if (array_key_exists("notificationURL", $extras) && !is_null($extras["notificationURL"])) {
        $aps_message->setCustomProperty('notificationURL', $extras["notificationURL"]);
      }
      $aps_message->setCustomProperty('userID', $user_id);
      $aps_message->setText($title);
      $this->pushService->add($aps_message);
    }
  }

  public function deliver() {
    $queue = $this->pushService->getQueue();
    if (!empty($queue)) {
      $this->pushService->send();
    }
    $aErrorQueue = $this->pushService->getErrors();
    if (!empty($aErrorQueue)) {
      foreach ($aErrorQueue as $error) {
        $tokens = $error["MESSAGE"]->getRecipients();
        $errors = $error["ERRORS"];
        $invalid_token = false;
        foreach ($errors as $device_error) {
          if ($device_error["statusCode"] == APNSNotificationProvider::ERROR_INVALID_TOKEN) {
            $invalid_token = true;
          }
        }
        foreach ($tokens as $token) {
          if ($invalid_token == true) {
            $this->logger.log("APNSError: INVALID TOKEN: " . $token);
            $userDevice = new UserDevice($this->session);
            $userDevice->deleteByToken($token);
          }
        }
      }
    }
  }

  public function disconnect() {
    $this->pushService->disconnect();
    $this->removeInvalidTokens();
  }

  public function test() {
    $this->deliver();
    $this->removeInvalidTokens();
  }


  /**
  * Selecciona un certificado válido para conexión con Apple
  */
  function chooseCeritifcate($type) {
    if ($type == APNSNotificationProvider::CERT_TYPE_APNS) {
      if ($this->environment == APNSNotificationProvider::ENVIRONMENT_SANDBOX) {
        return Conf::ServerDir() . '/../config/apns/apns-sandbox.pem';
      }
      if ($this->environment == APNSNotificationProvider::ENVIRONMENT_PRODUCTION) {
        return Conf::ServerDir() . '/../config/apns/apns-production.pem';
      }
    }
    if ($type == APNSNotificationProvider::CERT_TYPE_AUTH) {
      return Conf::ServerDir() . '/../config/apns/entrust_root_certification_authority.pem';
    }
  }

  /**
  * Elimina tokens inválidos de mensajes fallidos
  */
  function removeInvalidTokens() {
    $feedback = new ApnsPHP_Feedback(
      $this->environment,
      $this->chooseCeritifcate(APNSNotificationProvider::CERT_TYPE_APNS)
    );
    $feedback->connect();
    $aDeviceTokens = $feedback->receive();
    if (is_array($aDeviceTokens) && !empty($aDeviceTokens)) {
      foreach ($aDeviceTokens as $deviceToken) {
        $this->logger.log('APNSFeedback:' . $deviceToken);
      }
    }
    $feedback->disconnect();
  }

  /**
  * Obtiene los device Tokens del user_id seleccionado
  */
  function deviceTokensForUser($user_id) {
    $userDevice = new UserDevice($this->session);
    $tokens = $userDevice->tokensByUserId($user_id);
    return $tokens; //array("ed05ca868cc8d5c8393f2eb50a32ca007c37ad777dba3e17e9a23936a2df213b");
  }


}