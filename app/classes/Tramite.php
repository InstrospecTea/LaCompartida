<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Cobro.php';

class Tramite extends Objeto
{

	function Tramite($sesion, $fields = "", $params = "")
	{
		$this->tabla = "tramite";
		$this->campo_id = "id_tramite";
		$this->sesion = $sesion;
		$this->fields = $fields;

		if( $this->fields['duracion']=='00:00:00' ) $this->fields['duracion']='-';
	}

	function GuardarHistorial($queryHistorial) {
		$queryHistorial->run();
	}

	function QueryHistorial($tipo = 'CREAR', $app_id = 1) {
		$app_id = !is_null($app_id) ? $app_id : 1;
		$id_usuario_sesion = !is_null($this->sesion->usuario->fields['id_usuario']) ? $this->sesion->usuario->fields['id_usuario'] : $this->fields['id_usuario'];
		//Criterias
		$insertCriteria = new InsertCriteria($this->sesion);
		$insertCriteria->set_into('tramite_historial');

		$tramiteCriteria = new Criteria($this->sesion);
		$tramiteCriteria
			->add_select('codigo_asunto')
			->add_select('fecha', 'fecha_tramite')
			->add_select('id_tramite_tipo')
			->add_select('codigo_actividad')
			->add_select('codigo_tarea')
			->add_select('trabajo_si_no')
			->add_select('cobrable')
			->add_select('duracion')
			->add_select('descripcion')
			->add_select('tarifa_tramite')
			->add_select('id_usuario')
			->add_select('solicitante')
			->add_select('tarifa_tramite_individual')
			->add_select('id_moneda_tramite')
			->add_select('id_moneda_tramite_individual')
			->add_from('tramite')
			->add_restriction(CriteriaRestriction::equals('id_tramite', $this->fields['id_tramite']));

		$resultSet = $tramiteCriteria->run();
		$result = $resultSet[0];

		extract($result);

		$insertCriteria
			->add_pivot_with_value('id_tramite', $this->fields['id_tramite'])
			->add_pivot_with_value('accion', $tipo)
			->add_pivot_with_value('fecha', date("Y-m-d H:i:s"))
			->add_pivot_with_value('app_id', $app_id)
			->add_pivot_with_value('codigo_asunto', $codigo_asunto)
			->add_pivot_with_value('fecha_tramite', $fecha_tramite)
			->add_pivot_with_value('id_tramite_tipo', $id_tramite_tipo)
			->add_pivot_with_value('codigo_actividad', $codigo_actividad)
			->add_pivot_with_value('codigo_tarea', $codigo_tarea)
			->add_pivot_with_value('trabajo_si_no', empty($trabajo_si_no)? '0' : $trabajo_si_no)
			->add_pivot_with_value('cobrable', $cobrable)
			->add_pivot_with_value('duracion', $duracion)
			->add_pivot_with_value('descripcion', $descripcion)
			->add_pivot_with_value('tarifa_tramite', $tarifa_tramite)
			->add_pivot_with_value('id_usuario', $id_usuario_sesion)
			->add_pivot_with_value('solicitante', $solicitante)
			->add_pivot_with_value('tarifa_tramite_individual', $tarifa_tramite_individual)
			->add_pivot_with_value('id_moneda_tramite', $id_moneda_tramite)
			->add_pivot_with_value('id_moneda_tramite_individual', $id_moneda_tramite_individual);

		if ($tipo != 'ELIMINAR') {

			$insertCriteria
				->add_pivot_with_value('fecha_tramite_modificado', $this->fields['fecha'])
				->add_pivot_with_value('descripcion_modificado', $this->fields['descripcion'])
				->add_pivot_with_value('codigo_asunto_modificado', $this->fields['codigo_asunto'])
				->add_pivot_with_value('codigo_actividad_modificado', $this->fields['codigo_actividad'])
				->add_pivot_with_value('codigo_tarea_modificado', $this->fields['codigo_tarea'])
				->add_pivot_with_value('id_tramite_tipo_modificado', $this->fields['id_tramite_tipo'])
				->add_pivot_with_value('solicitante_modificado', $this->fields['solicitante'])
				->add_pivot_with_value('id_moneda_tramite_modificado', $this->fields['id_moneda_tramite'])
				->add_pivot_with_value('tarifa_tramite_modificado', $this->fields['tarifa_tramite'])
				->add_pivot_with_value('id_moneda_tramite_individual_modificado', $this->fields['id_moneda_tramite_individual'])
				->add_pivot_with_value('tarifa_tramite_individual_modificado', $this->fields['tarifa_tramite_individual'])
				->add_pivot_with_value('cobrable_modificado', $this->fields['cobrable'])
				->add_pivot_with_value('trabajo_si_no_modificado', empty($this->fields['trabajo_si_no'])? '0' : $this->fields['trabajo_si_no'])
				->add_pivot_with_value('duracion_modificado', $this->fields['duracion']);

		}

		return $insertCriteria;
	}

	function Write($historialOnWrite = true, $app_id = null) {
		$errandService = new ErrandService($this->sesion);
		$errand = new Errand();
		$errand->fillFromArray($this->fields);
		$errand->fillChangedFields($this->changes);
		try {
			$errandService->saveOrUpdate($errand);
		} catch(Exception $ex) {
			return false;
		}
		return true;
	}

	function Eliminar() {
		if($this->Estado() == "Abierto") {
			$errandService = new ErrandService($this->sesion);
			$errand = new Errand();
			$errand->fillFromArray($this->fields);
			$errandService->delete($errand);
			if($this->fields['trabajo_si_no']==1) {
				$query = "DELETE FROM trabajo WHERE id_tramite='".$this->fields['id_tramite']."'";
				mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			}

			$query = "DELETE FROM tramite WHERE id_tramite='".$this->fields['id_tramite']."'";;
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			// Si se pudo eliminar, loguear el cambio.
		} else {
			$this->error = __("No se puede eliminar un trámite que no está abierto");
			return false;
		}

		return true;
	}

	function get_codigo_cliente()
	{
		$query = "SELECT codigo_cliente FROM tramite JOIN asunto ON asunto.codigo_asunto=tramite.codigo_asunto
						WHERE id_tramite='".$this->fields['id_tramite']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$row = mysql_fetch_assoc($resp);

		return $row['codigo_cliente'];
	}

	function Estado() {
		if (!$this->fields['estadocobro'] && $this->fields['id_cobro']) {
			$cobro= new Cobro($this->sesion);
			$cobro->Load($this->fields['id_cobro']);
			$this->fields['estadocobro'] = $cobro->fields['estado'];
		}

		if ($this->fields['estadocobro'] <> 'CREADO'
			&& $this->fields['estadocobro'] <> 'EN REVISION'
			&& $this->fields['estadocobro'] != ''
			&& $this->fields['estadocobro'] != 'SIN COBRO') {
			return __('Cobrado');
		}

		if ($this->fields['revisado'] == 1) {
			return __('Revisado');
		}

		return __('Abierto');
	}

	function LoadId( $id_tramite )
    {
        $query = "SELECT * FROM tramite WHERE id_tramite='$id_tramite'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if( $this->fields = mysql_fetch_assoc($resp) )
        {
            $this->loaded = true;
            return true;
        }

        return false;
    }
}
if(!class_exists('ListaTramites')) {
	class ListaTramites extends Lista
	{
		function ListaTramites($sesion, $params, $query)
		{
			$this->Lista($sesion, 'Tramite', $params, $query);
		}
	}
}

