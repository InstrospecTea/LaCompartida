<?php
require_once dirname(__FILE__).'/../../conf.php';
##
##	Interfáz AJAX que maneja los request realizados en la interfaz de trabajos.
##	La lógica es utilizar un controlador llamado AjaxLedes para preparar una respuesta para
##	el request AJAX, la que finalmente se retorna.
##

extract($_POST);
$controlador = new AjaxLedes();
$respuesta = '';

switch ($opcion) {
	case 'ledes':
		if ($controlador->clienteSeExportaComoLedes($codigo_cliente)) {
			$respuesta = $controlador->renderizaControlesLedes(
					$conf_activa, 
					$codigo_tarea, 
					$permiso_revisor, 
					$permiso_profesional
				);
		} else {
			$respuesta = $controlador->respuestaVacia();
		}
		break;
	case 'act':
		if ($controlador->correspondeMostrarActividades($ledes, $actividades, $codigo_cliente)) {
			$respuesta = $controlador->renderizaControlesActividades(
				$codigo_actividad,
				$codigo_asunto
			);
		} else {
			$respuesta = $this->respuestaVacia();
		}
		break;
	default:
		$respuesta = $this->respuestaVacia();
		break;
}

echo $respuesta;

################################################################################################

/**
* Controlador que maneja y responde a los request AJAX.
*/
class AjaxLedes {
	


	/**
	 * Método que renderiza los controladores de LEDES, según corresponda.
	 * Parámetros:
	 *	- $conf_activa: True o False dependiendo si la configuración ExportacionLedes está activa.
	 *	- $codigo_tarea: 
	 *	- $permiso_revisor:  True o False dependiendo si el usuario tiene permiso de revisor o no.
	 *	- $permiso_profesional: True o False dependiendo si el usuario tiene permiso profesional o no.
	 */
	public function renderizaControlesLedes($conf_activa, $codigo_tarea, $permiso_revisor, $permiso_profesional) {

		if ($conf_activa && ($permiso_revisor || $permiso_profesional)) {
			$sesion = new Sesion(array('PRO', 'REV', 'SEC'));
			return '<td colspan="2" align=right>'.__('Código UTBMS').'</td><td align=left width="440" nowrap>'.InputId::ImprimirCodigo($sesion, 'UTBMS_TASK', 'codigo_tarea', $codigo_tarea).'</td>';
		} else {
			return '';
		}

	}

	public function renderizaControlesActividades($codigo_actividad, $codigo_asunto) {
		$sesion = new Sesion(array('PRO', 'REV', 'SEC'));
		$html = InputId::ImprimirActividad($sesion, 'actividad', 'codigo_actividad', 'glosa_actividad', 'codigo_actividad', $codigo_actividad, '', '', 320, $codigo_asunto);
		return '<td colspan="2" align=right>'.__('Actividad').'</td>'.'<td align=left width="440" nowrap>'.$html.'</td>';
	}

	/**
	*
	* Método que revisa si el cliente está configurado para que se exporte como LEDES.
	* Parámetros:
	*	- $codigo_cliente: Código del cliente del cual se cargará y revisará su contrato.
	*/
	public function clienteSeExportaComoLedes($codigo_cliente) {
		$sesion = new Sesion(array('DAT'));
		$contrato = new Contrato($sesion);
		$contrato->Load($codigo_cliente);
		return $contrato->fields['exportacion_ledes'];
	}

	/**
	 *  Método que revisa si correpsonde renderizar el control de actividades según la configuración
	 *	de código LEDES, Actividades y si el cliente se exporta como LEDES.
	 *	Parámetros:
	 *		- $ledes: True o False dependiendo de la configuracion LEDES.
	 *		- $actividades: True o False dependiendo de la configuración de actividades.
	 *		- $codigo_cliente: Para verificar si el cliente se exporta como LEDES.
	 */
	public function correspondeMostrarActividades($ledes, $actividades, $codigo_cliente) {

		if ($ledes && $this->clienteSeExportaComoLedes($codigo_cliente)) {
			return true;
		} 

		if ($actividades) {
			return true;
		}

		return false;

		// return true;
	}

	public function respuestaVacia() { return ''; }

}