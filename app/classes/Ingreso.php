<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Ingreso extends Objeto
{
	function Ingreso($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cliente_ingreso";
		$this->campo_id = "id_ingreso";
		$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	function Check()
	{
        # se chequea que el monto sea mayor a cero.
		if($this->changes[monto] == 1)
		{
			if($this->fields[monto] > 0)
				return true;
			else
			{
				$this->error = __("El monto debe ser mayor a cero");
				return false;
			}
		}
		return true;
	}
}

