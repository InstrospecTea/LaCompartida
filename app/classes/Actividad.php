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
	function Loaded()
	{
		if($this->fields['id_actividad'] == "")
			return false;
		return true;
	}
	function Editar() 
	{
		$glosa_actividad = $_POST["glosa_actividad"];
		$codigo_asunto = $_POST["codigo_asunto"];
		$id_actividad = $_POST["id_actividad"];

		if ($codigo_asunto != '') { 
			$query = "UPDATE actividad SET glosa_actividad = '". $glosa_actividad ."', codigo_asunto = '". $codigo_asunto ."', fecha_modificacion = NOW() 
				WHERE id_actividad = " .$id_actividad ;
		} else {
			$query = "UPDATE actividad SET glosa_actividad = '". $glosa_actividad ."', codigo_asunto = NULL, fecha_modificacion = NOW() 
				WHERE id_actividad = " .$id_actividad ;
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		return true;
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

	/**
	 * Load activity by code
	 * Returns a bool, true if exist record or false if doesn't exist
	 */
	function loadByCode($activity_code) {
		$sql = "SELECT `activity`.`id_actividad` AS `id`
			FROM `actividad` AS `activity`
			WHERE `activity`.`codigo_actividad`=:activity_code";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('activity_code', $activity_code);
		$Statement->execute();

		$activity = $Statement->fetch(PDO::FETCH_OBJ);

		if (is_object($activity)) {
			return $this->Load($activity->id);
		}

		return false;
	}
}

class ListaActividades extends Lista
{
    function ListaActividades($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Actividad', $params, $query);
    }
}
