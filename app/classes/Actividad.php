<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';

class Actividad extends Objeto
{
	function Actividad($sesion, $fields = "", $params = "")
	{
		$this->tabla = "actividad";
		$this->campo_id = "id_actividad";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	function Eliminar()
	{
		$query = "SELECT COUNT(*) FROM trabajo WHERE codigo_actividad = '".$this->fields['codigo_actividad']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$this->error = __('No se puede eliminar una actividad que tiene trabajos asociados.');
			return false;
		}
		else
		{
			$query = "DELETE FROM actividad WHERE codigo_actividad = '".$this->fields['codigo_actividad']."'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			return true;
		}

	}
	function Check()
	{
		$query = "SELECT COUNT(*) FROM actividad WHERE codigo_actividad = '".$this->fields['codigo_actividad']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$this->error = __('Ya existe una actividad con el código elegido.');
			return false;
		}
		return true;
	}
	//funcion que asigna el nuevo codigo automatico para un actividad
	function AsignarCodigoActividad()
	{
		$query = "SELECT codigo_actividad AS x FROM actividad ORDER BY x DESC LIMIT 1";
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    list($codigo) = mysql_fetch_array($resp);
		$f=$codigo+1;
	  $codigo_actividad=sprintf("%04d",$f);
	  return $codigo_actividad;
	}

	/**
	 * Find all activities
	 * Return an array with next elements:
	 * 	code, name and matter_code
	 */
	function findAll() {
		$activities = array();

		$sql = "SELECT `activity`.`codigo_asunto` AS `matter_code`, `activity`.`codigo_actividad` AS `code`,
			`activity`.`glosa_actividad` AS `name`
			FROM `actividad` AS `activity`
			ORDER BY `activity`.`glosa_actividad` ASC";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->execute();

		while ($activity = $Statement->fetch(PDO::FETCH_OBJ)) {
			array_push($activities,
				array(
					'code' => $activity->code,
					'name' => !empty($activity->name) ? $activity->name : null,
					'matter_code' => !empty($activity->matter_code) ? $activity->matter_code : null
				)
			);
		}

		return $activities;
	}
}

class ListaActividades extends Lista
{
    function ListaActividades($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Actividad', $params, $query);
    }
}
