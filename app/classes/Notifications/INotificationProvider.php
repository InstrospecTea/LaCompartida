<?php

/**
 *
 * @author @abraham_barrera
 */
interface INotificationProvider {
  /**
   * Connect to provider
   */
  public function connect();

  /**
   * Add Message to Stack
   */
  public function addMessage($user_id, $title, $extras);

  /**
   * Send messages in Stack
   */
  public function deliver();

  /**
   * Disconnect from provider
   */
  public function disconnect();

  /**
  * Make a test!
  */
  public function test();

}
