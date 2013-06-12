<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

class CronAlertaInconsistencia extends Cron {

	public $Sesion = null;

	public function __construct() {
		$this->Sesion = new Sesion();
	}

}

?>
