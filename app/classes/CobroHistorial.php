<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	
	class CobroHistorial extends Objeto
		{
			function CobroHistorial($sesion, $fields = "", $params = "")
				{
					$this->tabla = "cobro_historial";
					$this->campo_id = "id_cobro_historial";
					$this->sesion = $sesion;
					$this->fields = $fields;
				}
		}
		
	class ListaCobroHistorial extends Lista	
		{
			function ListaCobroHistorial( $sesion, $params, $query )
				{
					$this->Lista($sesion, 'CobroHistorial', $params, $query );
				}
		}
?>