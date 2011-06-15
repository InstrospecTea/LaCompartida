<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';

class DocumentoMoneda extends Objeto
{
	var $moneda = null;
	
	function DocumentoMoneda($sesion, $fields = "", $params = "")
	{
		$this->tabla 					= "documento_moneda";
		$this->campo_id				= "id_documento";
		$this->sesion 				= $sesion;
		$this->fields 				= $fields;
		$this->guardar_fecha 	= false;
	}
	
	
  function LoadByCobro($id_cobro)
  {
	  $query = "SELECT documento_moneda.id_moneda, documento_moneda.tipo_cambio, prm_moneda.cifras_decimales,prm_moneda.glosa_moneda, prm_moneda.simbolo 
    					FROM documento_moneda 
    					JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda
    					JOIN documento ON (documento.id_documento = documento_moneda.id_documento)
    					JOIN cobro ON (cobro.id_cobro = documento.id_cobro AND documento.tipo_doc = 'N')
					    WHERE cobro.id_cobro ='$id_cobro'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while( list($id_moneda, $tipo_cambio, $cifras_decimales,$glosa_moneda, $simbolo) = mysql_fetch_array($resp) )
		{
			$this->moneda[$id_moneda]['tipo_cambio'] 			= $tipo_cambio;
			$this->moneda[$id_moneda]['glosa_moneda'] 		= $glosa_moneda;
			$this->moneda[$id_moneda]['cifras_decimales'] = $cifras_decimales;
			$this->moneda[$id_moneda]['simbolo'] 					= $simbolo;
		}
		return true;
  }
  
  
  function Load($id_documento)
  {
    $query = "SELECT documento_moneda.id_moneda, documento_moneda.tipo_cambio, prm_moneda.cifras_decimales,prm_moneda.glosa_moneda, prm_moneda.simbolo 
    					FROM documento_moneda 
    					JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda
					    WHERE id_documento ='$id_documento'";
    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    while( list($id_moneda, $tipo_cambio, $cifras_decimales,$glosa_moneda, $simbolo) = mysql_fetch_array($resp) )
    {
    	$this->moneda[$id_moneda]['tipo_cambio'] 			= $tipo_cambio;
    	$this->moneda[$id_moneda]['glosa_moneda'] 		= $glosa_moneda;
    	$this->moneda[$id_moneda]['cifras_decimales'] = $cifras_decimales;
    	$this->moneda[$id_moneda]['simbolo'] 					= $simbolo;
    }
    return true;
  }
	
	/* 
		Obtiene el tipo cambio de la moneda del documento
		según el ID_MONEDA e ID_DOCUMENTO
	*/
	function GetTipoCambio($id_documento,$id_moneda)
	{
		$sql = "SELECT tipo_cambio FROM documento_moneda WHERE id_moneda = ".$id_moneda." AND id_documento = ".$id_documento;
		$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
		list($tipo_cambio) = mysql_fetch_array($resp);
		return $tipo_cambio;
	}
	
	/*
		UPDATE tipo cambio de documento
	*/
	function UpdateTipoCambioDocumento($id_moneda, $tipo_cambio, $id_documento)
	{
		$sql = "UPDATE documento_moneda SET tipo_cambio = $tipo_cambio WHERE id_documento = $id_documento AND id_moneda = $id_moneda LIMIT 1";
		$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}	
}
?>