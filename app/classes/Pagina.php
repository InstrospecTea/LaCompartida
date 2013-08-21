<?php
namespace TTB;
require_once dirname(__FILE__) . '/../conf.php';
use \Conf;
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';

class Pagina extends \Pagina {

	function PrintTop($popup = false, $color = '', $color_barra = '#42a62b') {
		$this->PrintHeaders($color_barra);
		if (stripos($_SERVER['SCRIPT_FILENAME'], '/admin/') === true && !$sesion->usuario->TienePermiso('SADM')) {
			die('No Autorizado');
		}

		if (method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaDisenoNuevo')) {
			if (!$popup) {
				require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top_nuevo.php';
			} else {
				require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top_popup_nuevo.php';
			}
		} else {
			if (!$popup) {
				require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top.php';
			} else {
				require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top_popup.php';
			}
		}
	}

	function PrintBottom($popup = false, $forzar_bottom_antiguo = false) {
		echo '<script type="text/javascript"> var intervalo ='. Conf::GetConf($this->sesion, 'Intervalo') .';</script>';

		if ((method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaDisenoNuevo')) && $forzar_bottom_antiguo == false)  {
			if (!$popup) {
				require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom_nuevo.php';
			} else {
				require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom_popup_nuevo.php';
			}
		} else {
			if (!$popup) {
				require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom.php';
			} else {
				require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom_popup.php';
			}
		}
	}

}
