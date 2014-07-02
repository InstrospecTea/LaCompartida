<?php
require_once dirname(__FILE__).'/../conf.php';

################################################################################################

/**
* Controlador que maneja y responde a los request AJAX.
*/

class AjaxLedes {

	public $Sesion;

	public function __construct() {
		$this->Sesion = New Sesion();
	}


	// /**
	//  * Método que renderiza los controladores de LEDES, según corresponda.
	//  * Parámetros:
	//  *	- $conf_activa: True o False dependiendo si la configuración ExportacionLedes está activa.
	//  *	- $codigo_tarea:
	//  *	- $permiso_revisor:  True o False dependiendo si el usuario tiene permiso de revisor o no.
	//  *	- $permiso_profesional: True o False dependiendo si el usuario tiene permiso profesional o no.
	//  */

	public function renderizaControlesLedes($codigo_tarea) {
		return '<td colspan="2" align=right>'.__('C&oacute;digo UTBMS').'</td><td align=left width="440" nowrap>'.InputId::ImprimirCodigo($this->Sesion, 'UTBMS_TASK', 'codigo_tarea', $codigo_tarea).'</td>';
	}

	public function renderizaControlesActividades($codigo_actividad, $codigo_asunto) {
		$html = InputId::Imprimir($this->Sesion, 'actividad', 'codigo_actividad', 'glosa_actividad', 'codigo_actividad', $codigo_actividad, '', '', 320, $codigo_asunto);
		return '<td colspan="2" align=right>'.__('Actividad').'</td>'.'<td align=left width="440" nowrap>'.$html.'</td>';
	}

	// /**
	// *
	// * Método que revisa si el cliente está configurado para que se exporte como LEDES.
	// * Parámetros:
	// *	- $codigo_cliente: Código del cliente del cual se cargará y revisará su contrato.
	// */

	public function clienteSeExportaComoLedes($codigo_cliente) {

		$criteria = new Criteria($this->Sesion);
		$criteria->add_select('exportacion_ledes')
				->add_from('contrato')
				->add_left_join_with('cliente',CriteriaRestriction::equals('cliente.id_contrato', 'contrato.id_contrato'))
		 		->add_restriction(
		 				CriteriaRestriction::equals('cliente.codigo_cliente',$codigo_cliente)
		 		);

		try{
			$result = $criteria->run();
		} catch (PDOException $ex) {
			return false;
		}

		$exporta_ledes = $result[0]['exportacion_ledes'];

		if (!empty($exporta_ledes)) {

			if ($exporta_ledes == 1) {
				return true;
			} else {
				return false;
			}

		} else {
			return false;
		}

	}

	public function correspondeMostrarLedes($configuracion_ledes, $permiso_revisor, $permiso_profesional, $codigo_cliente) {
		 if ($configuracion_ledes && $this->clienteSeExportaComoLedes($codigo_cliente)) {
		 	if ($permiso_profesional || $permiso_revisor) {
		 		return true;
		 	}
		}

		return false;
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

		if ($actividades && !$ledes) {
			return true;
		}

		if ($actividades && $ledes && !$this->clienteSeExportaComoLedes($codigo_cliente)) {
			return false;
		}

		return false;
	}

	public function respuestaVacia() { return ''; }

}