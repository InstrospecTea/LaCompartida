<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	
	class GastoHistorial extends Objeto
		{
			function GastoHistorial($sesion, $fields="", $params="")
				{
					$this->tabla = "gasto_historial";
					$this->tabla_id = "id_gasto_historial";
					$this->sesion = $sesion;
					$this->fields = $fields;
				}
		}
		
	class ListaGastoHistorial extends Lista
		{
			function ListaGastoHistorial($sesion, $params, $query )
				{
					$this->Lista($sesion, 'GastoHistorial', $params, $query );
				}
		}
?>