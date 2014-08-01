<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Description of CronAnulaDTE
 *
 * @author ABX 1.0
 */
class CronAnulaDTE extends Cron {

	var $Sesion = null;

	public function __construct($Sesion) {
		$this->fecha_cron = date('Y-m-d');
		$this->FileNameLog = 'CronAnulaDTE';

		parent::__construct();
		$this->Sesion = $Sesion;

		date_default_timezone_set(Conf::GetConf($this->Sesion, 'ZonaHoraria'));
	}

	public function main() {
		$this->log('INICIO CronAnulaDTE');
		$this->Sesion->phpConsole(1);
		$factura = new Factura($this->Sesion);
		$listado = $factura->ObtenerEnProcesoAnulacion();
		foreach ($listado->datos as $f) {
			if (!$f->DTEAnulado()) {
				$data = array('Factura' => $f);
				$res = ($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_anula_factura_electronica', &$data) : false;
				$error = $data['Error'];
				$numero = $f->fields['id_factura'];
				if (!is_null($error)) {
					$message = "<br/>Documento Tributario asociado a factura id={$numero}: ";
					$this->log($message);
					$message = $error['Message'] ? $error['Message'] : __($error['Code']);
					$this->log($message);
				} else {
					$message = "<br/>Documento Tributario asociado a factura id={$numero} Anulado exitosamente";
					$this->log($message);
				}
			}
		}
	}

}