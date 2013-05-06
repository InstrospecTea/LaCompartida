<?php

require_once dirname(__FILE__) . '../../../conf.php';
require_once Conf::ServerDir() . '/classes/Notifications/APNSNotificationProvider.php';

class NotificationService {

  const ENVIRONMENT_PRODUCTION = 0;
  const ENVIRONMENT_SANDBOX = 1;

  function NotificationService($session, $options) {
    $this->options = $options;
    $this->session = $session;
    $this->providers = null;
    $this->expandOptions();
    $this->connectProviders();
  }

  function logError($error) {
    echo $error;
  }

  function expandOptions() {
    $this->environment = NotificationService::ENVIRONMENT_SANDBOX;
    if (is_array($this->options) && !empty($this->options)) {
      if (!is_null($this->options['environment'])) {
        $this->environment = $this->options['environment'];
      }
      if (is_array($this->options['providers']) &&
         !empty($this->options['providers'])) {
        $this->providers = array();
        foreach ($this->options['providers'] as $provider) {
          $provider_instance = new $provider($this->session, $this->environment);
          if (!is_null($provider_instance)) {
            array_push($this->providers, $provider_instance);
          }
        }
      }
    }
  }

  function hasProvider() {
    return (is_array($this->providers) && !empty($this->providers));
  }

  function connectProviders() {
    if ($this->hasProvider()) {
      foreach ($this->providers as $provider) {
        $provider->connect();
      }
    } else {
      $this->logError('You must select a notification provider');
    }
  }

  public function addMessage($user_id, $title, $extras) {
    if ($this->hasProvider()) {
      foreach ($this->providers as $provider) {
        $provider->addMessage($user_id, $title, $extras);
      }
    } else {
      $this->logError('You must select a notification provider');
    }
  }

  public function deliver() {
    if ($this->hasProvider()) {
      foreach ($this->providers as $provider) {
        $provider->deliver();
        $provider->disconnect();
      }
    }
  }
}
