<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';

class CtaCteFactMoneda extends Objeto
{
	var $moneda = null;
	var $base = null;
	
	function CtaCteFactMoneda($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cta_cte_fact_mvto_moneda";
		$this->campo_id = "id_cta_cte_fact_mvto";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}
	
	function Load($id_cta_cte_fact_mvto)
  {
		$ok = false;
    $query = "SELECT cta_cte_fact_mvto_moneda.id_moneda, cta_cte_fact_mvto_moneda.tipo_cambio, prm_moneda.cifras_decimales,prm_moneda.glosa_moneda, prm_moneda.simbolo, prm_moneda.moneda_base
    					FROM cta_cte_fact_mvto_moneda 
    					JOIN prm_moneda ON cta_cte_fact_mvto_moneda.id_moneda = prm_moneda.id_moneda
					    WHERE id_cta_cte_fact_mvto ='$id_cta_cte_fact_mvto'";
    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    while( list($id_moneda, $tipo_cambio, $cifras_decimales,$glosa_moneda, $simbolo, $moneda_base) = mysql_fetch_array($resp) )
    {
    	$this->moneda[$id_moneda]['tipo_cambio'] 	= $tipo_cambio;
    	$this->moneda[$id_moneda]['glosa_moneda'] 	= $glosa_moneda;
    	$this->moneda[$id_moneda]['cifras_decimales'] 		= $cifras_decimales;
    	$this->moneda[$id_moneda]['simbolo'] 			= $simbolo;

		if(!empty($moneda_base)) $this->base = $this->moneda[$id_moneda];
		$ok = true;
    }
    return $ok;
  }
	
	
	/*
		Monedas del mvto -> se guardan en cta_cte_fact_mvto_moneda cada tipo 
		cambio de las monedas al monento de crear el mvto
		si no se pasa el tipo_cambio se saca del cobro, si tampoco se pasa se saca de prm_moneda
	*/
	function ActualizarTipoCambioMvto($id_cta_cte_fact_mvto, $tipos_cambio = array(), $id_cobro = null)
	{
		$sql = "SELECT COUNT(*) FROM cta_cte_fact_mvto_moneda WHERE id_cta_cte_fact_mvto = '".$id_cta_cte_fact_mvto."'";
		$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			return false;
		}
		else
		{
			if(empty($tipos_cambio)){
				$tipos_cambio = array();

				$query_monedas = "SELECT id_moneda, tipo_cambio FROM ".(empty($id_cobro) ? "prm_moneda" : "cobro_moneda WHERE id_cobro = '$id_cobro'");

				$resp2 = mysql_query($query_monedas);
				while($row = mysql_fetch_array($resp2))
				{
					$tipos_cambio[] = $row;
				}
			}
			foreach($tipos_cambio as $row)
			{
				$query_insert = "INSERT INTO cta_cte_fact_mvto_moneda SET id_cta_cte_fact_mvto = ".$id_cta_cte_fact_mvto.", id_moneda = ".$row['id_moneda'].", tipo_cambio = ".$row['tipo_cambio']." ";
				$result = mysql_query($query_insert);
			}
			return true;
		}
	}
	
	/* 
		Obtiene el tipo cambio de la moneda del mvto 
		según el ID_MONEDA e id_cta_cte_fact_mvto 
	*/
	function GetTipoCambio($id_cta_cte_fact_mvto,$id_moneda)
	{
		$sql = "SELECT tipo_cambio FROM cta_cte_fact_mvto_moneda WHERE id_moneda = ".$id_moneda." AND id_cta_cte_fact_mvto = ".$id_cta_cte_fact_mvto;
		$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
		list($tipo_cambio) = mysql_fetch_array($resp);
		return $tipo_cambio;
	}
	
	/*
		UPDATE tipo cambio de mvto
	*/
	function UpdateTipoCambioMvto($id_moneda, $tipo_cambio, $id_cta_cte_fact_mvto)
	{
		$sql = "UPDATE cta_cte_fact_mvto_moneda SET tipo_cambio = $tipo_cambio WHERE id_cta_cte_fact_mvto = $id_cta_cte_fact_mvto AND id_moneda = $id_moneda LIMIT 1";
		$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}	

	function GetMontoBase($monto, $id_moneda, $id_cta_cte_fact_mvto = null)
	{
		if($id_cta_cte_fact_mvto)
			$this->Load($id_cta_cte_fact_mvto);

		if(empty($this->moneda)) return $monto; //tirar un error? usar moneda actual?

		return $monto * $this->moneda[$id_moneda]['tipo_cambio'] / $this->base['tipo_cambio'];
	}
}
?>
