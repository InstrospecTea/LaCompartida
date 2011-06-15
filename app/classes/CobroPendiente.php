<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';

#Fechas de los cobros estimados de los contratos
class CobroPendiente extends Objeto
{
	function CobroPendiente($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cobro_pendiente";
		$this->campo_id = "id_cobro_pendiente";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}
	
	#asocia los cobros pendientes por fecha y contrato
	function AsociarCobro($sesion,$id_cobro)
	{
		$query = "UPDATE cobro_pendiente SET id_cobro='$id_cobro' WHERE id_cobro_pendiente='".$this->fields['id_cobro_pendiente']."'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		return true;
	}
	
	#se borran todas las fechas del contrato
	function EliminarPorContrato($sesion,$id_contrato)
	{
		$query = "DELETE FROM cobro_pendiente WHERE id_contrato = '$id_contrato' AND id_cobro IS NULL";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	}
	
	#funcion que se corre una vez al mes por cron para generar los nuevos
	function GenerarCobrosPeriodicos($sesion)
	{
		$query = "SELECT id_contrato,periodo_intervalo,periodo_repeticiones,monto,
							forma_cobro
							FROM contrato
							WHERE contrato.activo='SI'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while($contrato = mysql_fetch_array($resp))
		{
			#se saca la ultima fecha en la lista del contrato
			$query2 = "SELECT SQL_CALC_FOUND_ROWS fecha_cobro FROM cobro_pendiente 
									WHERE id_contrato='".$contrato['id_contrato']."' ORDER BY fecha_cobro DESC LIMIT 1";
			$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
			list($ultima_fecha) = mysql_fetch_array($resp2);

			#cantidad de cobros pendientes
			$query3 = "SELECT FOUND_ROWS()";
			$resp3 = mysql_query($query3, $sesion->dbh) or Utiles::errorSQL($query3,__FILE__,__LINE__,$sesion->dbh);
			list($numero_pendientes) = mysql_fetch_array($resp3);

			#datos del siguiente cobro pendiente por contrato
			if($numero_pendientes > 0 && $contrato['periodo_repeticiones']==0 && $contrato['periodo_intervalo']!=0 && ($numero_pendientes*$contrato['periodo_intervalo']) < 24)
			{
				$numero_pendientes++;
				$siguiente_fecha = strtotime(date("Y-m-d", strtotime($ultima_fecha)) . " +".$contrato['periodo_intervalo']." month");
				$query4 = "INSERT INTO cobro_pendiente (id_contrato,fecha_cobro,descripcion,monto_estimado) 
										VALUES (".$contrato['id_contrato'].",'".$siguiente_fecha."',
										'Cobro N° ".$numero_pendientes."',
										".(($contrato['forma_cobro']=='FLAT FEE' || $contrato['forma_cobro']=='RETAINER') ? $contrato['monto'] : '').")";
					mysql_query($query4, $sesion->dbh) or Utiles::errorSQL($query4,__FILE__,__LINE__,$sesion->dbh);
			}
		}
		return true;
	}
}

class ListaCobrosPendientes extends Lista
{
    function ListaCobrosPendientes($sesion, $params, $query)
    {
        $this->Lista($sesion, 'CobroPendiente', $params, $query);
    }
}