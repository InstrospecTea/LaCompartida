<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once 'ApnsPHP/Autoload.php';

class NotificationService {

  const ENVIRONMENT_PRODUCTION = 0;
  const ENVIRONMENT_SANDBOX = 1;
  const PROVIDER_APNS = 0; //Provider debiera ser interfaz

  const CERT_TYPE_APNS = 0;
  const CERT_TYPE_AUTH = 1;

  function NotificationService($sesion, $options) {
    $this->options = $options;
    $this->sesion = $sesion;
    $this->expandOptions();
    $this->connectToProvider();
  }

  function logError($error) {
    echo $error;
  }

  function expandOptions() {
    $this->pushService = null;
    $this->environment = NotificationService::ENVIRONMENT_SANDBOX;

    if (is_array($this->options) && !empty($this->options)) {
      if (!is_null($this->options['environment'])) {
        $this->environment = $this->options['environment'];
      }
      if (!is_null($this->options['provider'])) {
        $this->provider = $this->options['provider'];
      }
    }

  }

  function chooseCeritifcate($type) {
     if (!is_null($this->provider) && $this->provider == NotificationService::PROVIDER_APNS) {
        if ($type == NotificationService::PROVIDER_APNS) {
          if ($this->environment = NotificationService::ENVIRONMENT_SANDBOX) {
            return dirname(__FILE__) . '/../../config/apns/apns-sandbox.pem';
          }
          if ($this->environment = NotificationService::ENVIRONMENT_PRODUCTION) {
            return dirname(__FILE__) . '/../../config/apns/server_certificates_bundle_production.pem';
          }
        }
        if ($type == NotificationService::CERT_TYPE_AUTH) {
          return dirname(__FILE__) . '/../../config/apns/entrust_root_certification_authority.pem';
        }
     }
  }

  function connectToProvider() {
    if (!is_null($this->provider) && $this->provider == NotificationService::PROVIDER_APNS) {
      $certificate = $this->chooseCeritifcate(NotificationService::CERT_TYPE_APNS);
      $certificationAuth = $this->chooseCeritifcate(NotificationService::CERT_TYPE_AUTH);
      if ($certificate) {
        $this->pushService = new ApnsPHP_Push($this->environment, $certificate);
        $this->pushService->setRootCertificationAuthority($certificationAuth);
        $this->pushService->connect();
      } else {
        $this->logError('No SSL certificate provide');
      }
    }
  }

  public function addMessage($user_id, $message, $extra_information) {
    if (!is_null($this->provider) && $this->provider == NotificationService::PROVIDER_APNS) {
      $tokens = $this->tokensForUser($user_id);
      if (is_array($tokens) && !empty($tokens)) {
        foreach ($tokens as $token) {
          $aps_message = new ApnsPHP_Message($token);
          $aps_message->setCustomIdentifier(sprintf("Message-Badge-%03d", 11));
          $aps_message->setText($message);
          $aps_message->setBadge(0);
          $aps_message->setSound();
          if (!is_null($extra_information["notificationMessage"])) {
            $aps_message->setCustomProperty('notificationMessage', $extra_information["notificationMessage"]);
          }
          if (!is_null($extra_information["notificationURL"])) {
            $aps_message->setCustomProperty('notificationURL', $extra_information["notificationURL"]);
          }
          $this->pushService->add($aps_message);
        }
      }
    }
  }

  public function deliver() {
    if (!is_null($this->provider) && $this->provider == NotificationService::PROVIDER_APNS) {
      $this->pushService->send();
      $this->pushService->disconnect();
      $aErrorQueue = $this->pushService->getErrors();
      if (!empty($aErrorQueue)) {
        var_dump($aErrorQueue);
      }
    }
  }

  public function tokensForUser($user_id) {
    return array("ed05ca868cc8d5c8393f2eb50a32ca007c37ad777dba3e17e9a23936a2df213b");
  }

}
