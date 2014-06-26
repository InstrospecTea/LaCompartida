<?php

require_once dirname(__FILE__).'/../../conf.php';
##
##	Interfáz AJAX que maneja los request realizados en la interfaz de trabajos.
##	La lógica es utilizar un controlador llamado AjaxLedes para preparar una respuesta para
##	el request AJAX, la que finalmente se retorna.
##
extract($_POST);
$AjaxLedes = new AjaxLedes();
$respuesta = '';

exit;

switch ($opcion) {
	case 'ledes':
		if ($AjaxLedes->correspondeMostrarLedes($conf_activa, $permiso_revisor, $permiso_profesional, $codigo_cliente)) {
			$respuesta = $AjaxLedes->renderizaControlesLedes($codigo_tarea);
		} else {
			echo 'falla 1';
			$respuesta = $AjaxLedes->respuestaVacia();
		}
		break;
	case 'act':
		if ($AjaxLedes->correspondeMostrarActividades($ledes, $actividades, $codigo_cliente)) {
			$respuesta = $AjaxLedes->renderizaControlesActividades($codigo_actividad, $codigo_asunto);
		} else {
			echo 'falla 2';
			$respuesta = $this->respuestaVacia();
		}
		break;
	default:
		echo 'falla 3';
		$respuesta = $this->respuestaVacia();
		break;
}

echo $respuesta;

