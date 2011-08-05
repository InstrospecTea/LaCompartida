<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class TramiteTarifa extends Objeto
{
	function TramiteTarifa($sesion, $fields = "", $params = "")
	{
		$this->tabla = "tramite_tarifa";
		$this->campo_id = "id_tramite_tarifa";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	
	#Carga a través de ID
	function LoadById($id_tramite_tarifa)
	{
		$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE id_tramite_tarifa = '$id_tramite_tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}
	
	
	# Elimina Tarifas
	function Eliminar()
	{
		if(!$this->Loaded())
			return false;
		/*$query = "SELECT COUNT(*) FROM contrato WHERE contrato.id_tarifa = '".$this->fields['id_tarifa']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$query = "SELECT codigo_cliente FROM contrato WHERE id_tarifa = '".$this->fields['id_tarifa']."' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($contrato) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar una').' '.__('tarifa').' '.__('que tiene un contrato asociado. Código contrato asociado: ').$contrato;
			return false;
		}*/
		$query = "DELETE FROM tramite_valor WHERE id_tramite_tarifa = '".$this->fields['id_tramite_tarifa']."'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		
		$query = "DELETE FROM tramite_tarifa WHERE id_tramite_tarifa = '".$this->fields['id_tramite_tarifa']."'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}


	#Carga la tarifa para el usuario y moneda correspondiente
	function LoadByTramiteMoneda($id_tramite_tipo, $id_moneda, $id_tramite_tarifa)
	{
		$query = "SELECT id_tramite_valor FROM tramite_valor WHERE id_tramite_tipo = '$id_tramite_tipo' AND id_moneda = '$id_moneda' AND id_tarifa = '$id_tramite_tarifa' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $id;
	}
	
	#Limpia Tarifa por defecto
	function TarifaDefecto($id_tramite_tarifa)
	{
		$query = "UPDATE tramite_tarifa SET tarifa_defecto = 0 WHERE id_tramite_tarifa != '$id_tramite_tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
	
	#Retorna ID tarifa declarada como defecto
	function SetTarifaDefecto()
	{
		$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto = 1 LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id_tramite_tarifa) = mysql_fetch_array($resp);
		return $id_tramite_tarifa;
	}
	
}

Class TramiteValor extends Objeto
{
	function TramiteValor($sesion, $fields = "", $params = "")
	{
		$this->tabla = "tramite_valor";
		$this->campo_id = "id_tramite_valor";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	
	#Carga a través de ID_TARIF, ID_USUARIO, ID_MONEDA la tarifa(monto)
	function LoadById($id_tramite_valor, $id_tramite_tipo, $id_moneda)
	{
		$query = "SELECT tarifa FROM tramite_valor WHERE id_tramite_valor = '$id_tramite_valor' AND id_moneda = '$id_moneda' AND id_tramite_tipo = '$id_tramite_tipo' LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($tarifa) = mysql_fetch_array($resp);
		return $tarifa;
	}
	
	#Guardando Tarifa
	function GuardarTarifa( $id_tramite_tarifa, $id_tramite_tipo, $id_moneda, $valor)
	{
		$valor=str_replace(',','.',$valor);
		if( empty($id_tramite_tarifa) ) 
		{
			$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto = 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list( $id_tramite_tarifa ) = mysql_fetch_array($resp);
		}
		if( empty($id_tramite_tarifa) )
			return false;
		if($valor == '')
		{
			$query = "DELETE FROM tramite_valor WHERE id_moneda = '$id_moneda' AND id_tramite_tipo = '$id_tramite_tipo' AND id_tramite_tarifa = '$id_tramite_tarifa'";
		}
		else
		{
			$query = "INSERT tramite_valor SET id_moneda = '$id_moneda', 
								id_tramite_tipo = '$id_tramite_tipo', id_tramite_tarifa = '$id_tramite_tarifa' , tarifa = '$valor'
								ON DUPLICATE KEY UPDATE tarifa = '$valor'";
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
}
?>