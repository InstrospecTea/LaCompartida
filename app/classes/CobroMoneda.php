<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';

class CobroMoneda extends Objeto
{
	var $moneda = null;
	var $moneda_cobro = null;
	
	function CobroMoneda($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cobro_moneda";
		$this->campo_id = "id_cobro";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}
	
	function Load($id_cobro)
  {
    $query = "SELECT cobro_moneda.id_moneda, cobro_moneda.tipo_cambio, prm_moneda.cifras_decimales,prm_moneda.glosa_moneda, prm_moneda.simbolo 
    					FROM cobro_moneda 
    					JOIN prm_moneda ON cobro_moneda.id_moneda = prm_moneda.id_moneda
					    WHERE id_cobro ='$id_cobro'";
    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    while( list($id_moneda, $tipo_cambio, $cifras_decimales,$glosa_moneda, $simbolo) = mysql_fetch_array($resp) )
    {
    	$this->moneda[$id_moneda]['tipo_cambio'] 	= $tipo_cambio;
    	$this->moneda[$id_moneda]['glosa_moneda'] 	= $glosa_moneda;
    	$this->moneda[$id_moneda]['cifras_decimales'] 		= $cifras_decimales;
    	$this->moneda[$id_moneda]['simbolo'] 			= $simbolo;
    }

    $query = "SELECT opc_moneda_total FROM cobro WHERE id_cobro ='$id_cobro'";
    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    list($id_moneda) = mysql_fetch_array($resp);
	$this->moneda_cobro = $this->moneda[$id_moneda];
	$this->moneda_cobro['id_moneda'] = $id_moneda;

    return true;
  }
	
	
	/*
		Monedas del cobro -> se guardan en cobro_moneda cada tipo 
		cambio de las monedas al monento de crear el cobro
	*/
	function ActualizarTipoCambioCobro($id_cobro)
	{
		$sql = "SELECT COUNT(*) FROM cobro_moneda WHERE id_cobro = ".$id_cobro;
		$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			return false;
		}
		else
		{
			$query_monedas = "SELECT id_moneda, tipo_cambio FROM prm_moneda";
			$resp2 = mysql_query($query_monedas);
			while($row = mysql_fetch_array($resp2))
			{
				$row['id_moneda'];
				$query_insert = "INSERT INTO cobro_moneda SET id_cobro = ".$id_cobro.", id_moneda = ".$row['id_moneda'].", tipo_cambio = ".$row['tipo_cambio']." ";
				$result = mysql_query($query_insert);
			}
			return true;
		}
	}
	
	/* 
		Obtiene el tipo cambio de la moneda del cobro 
		según el ID_MONEDA e ID_COBRO 
	*/
	function GetTipoCambio($id_cobro,$id_moneda)
	{
		$sql = "SELECT tipo_cambio FROM cobro_moneda WHERE id_moneda = ".$id_moneda." AND id_cobro = ".$id_cobro;
		$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
		list($tipo_cambio) = mysql_fetch_array($resp);
		return $tipo_cambio;
	}
	
	/*
		UPDATE tipo cambio de cobro
	*/
	function UpdateTipoCambioCobro($id_moneda, $tipo_cambio, $id_cobro)
	{
		$sql = "UPDATE cobro_moneda SET tipo_cambio = $tipo_cambio WHERE id_cobro = $id_cobro AND id_moneda = $id_moneda LIMIT 1";
		$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}	
}
?>
