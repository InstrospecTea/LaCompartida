<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Moneda.php';
require_once Conf::ServerDir().'/../app/classes/CtaCteFactMvtoNeteo.php';

class CtaCteFactMvto extends Objeto
{
	function CtaCteFactMvto($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cta_cte_fact_mvto";
		$this->campo_id = "id_cta_cte_mvto";
		#$this->guardar_fecha = false;
		if($sesion == "")
			global $sesion;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function LoadByFactura($id_factura, $tipo_mvto='F')
	{
		$query = "SELECT id_cta_cte_mvto FROM cta_cte_fact_mvto WHERE id_factura = '$id_factura'";// AND tipo_mvto='$tipo_mvto';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if($id)
			return $this->Load($id);
		return false;
	}

	function LoadByPago($id_pago)
	{
		$query = "SELECT id_cta_cte_mvto FROM cta_cte_fact_mvto WHERE id_factura_pago = '$id_pago'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		
		if($id)
			return $this->Load($id);
		return false;
	}
	
	function ActualizarMvtoMoneda($tipo_cambio = array())
	{
			$query = "DELETE FROM cta_cte_fact_mvto_moneda WHERE id_cta_cte_fact_mvto = '".$this->fields['id_cta_cte_mvto']."'";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $this->sesion->dbh);
			
			if(empty($tipo_cambio))
			{
				$query = "INSERT INTO cta_cte_fact_mvto_moneda (id_cta_cte_mvto, id_moneda, tipo_cambio)
					SELECT '".$this->fields['id_cta_cte_mvto']."', id_moneda, tipo_cambio
					FROM prm_moneda WHERE 1";
				$resp =mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $this->sesion->dbh);
			}
			else foreach($tipo_cambio as $id_moneda => $tc)
			{
				$query = "INSERT INTO cta_cte_fact_mvto_moneda (id_cta_cte_fact_mvto, id_moneda, tipo_cambio)
									VALUES ('".$this->fields['id_cta_cte_mvto']."',".$id_moneda.",".$tc.");";
				$resp =mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $this->sesion->dbh);
			}
	}

	function GetNeteosSoyDeuda(){
		$query = "SELECT * FROM cta_cte_fact_mvto_neteo WHERE id_mvto_deuda = '".$this->Id()."'";
		return new ListaCtaCteFactMvtoNeteo($this->sesion, null, $query);
	}
	
	function GetIdDocumentoLiquidacionSoyMvto()
	{
		$query = "SELECT id_documento 
								FROM documento
								JOIN factura_pago ON documento.id_factura_pago = factura_pago.id_factura_pago 
								JOIN cta_cte_fact_mvto ON factura_pago.id_factura_pago = cta_cte_fact_mvto.id_factura_pago 
							WHERE cta_cte_fact_mvto.id_cta_cte_mvto = '".$this->Id()."'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id_documento) = mysql_fetch_array($resp);
		if( $id_documento > 0 )
			return $id_documento;
		else
			return false;
	}
	
	
	function GetNeteosSoyPago($id=null){
		if(!$id){ $id = $this->Id(); }
		$query = "SELECT * FROM cta_cte_fact_mvto_neteo WHERE id_mvto_pago = '".$id."'";
		return new ListaCtaCteFactMvtoNeteo($this->sesion, null, $query);
	}

	function GetSaldoDeuda($lista_id)
	{
		$query = "SELECT saldo FROM cta_cte_fact_mvto WHERE id_factura in (".$lista_id.")";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($saldo) = mysql_fetch_array($resp);
		return $saldo;
	}

	function Id($id=null){
		if($id) $this->fields[$this->campo_id] = $id;
		if(empty($this->fields[$this->campo_id])) return false;
		return $this->fields[$this->campo_id];
	}
}
