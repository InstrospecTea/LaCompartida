<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

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
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		return true;
	}
	
	#se borran todas las fechas del contrato
	function EliminarPorContrato($sesion,$id_contrato)
	{
		$query = "DELETE FROM cobro_pendiente WHERE id_contrato = '$id_contrato' AND id_cobro IS NULL";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
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

	function MontoHitosPorLiquidar($contrato)
	{
		$sql = "SELECT SUM(monto_estimado) AS monto FROM cobro_pendiente WHERE id_cobro IS NULL AND hito = 1 AND id_contrato = " . $contrato;
		$query = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
		list($monto) = mysql_fetch_array($query);
		return empty($monto) ? 0 : $monto;
	}

	function MontoHitosLiquidados($contrato)
	{
		$sql = "SELECT SUM(cobro_pendiente.monto_estimado) AS monto 
		FROM cobro_pendiente
			JOIN cobro ON cobro_pendiente.id_cobro = cobro.id_cobro
		WHERE cobro_pendiente.id_cobro IS NOT NULL AND 
		cobro_pendiente.hito = 1 AND 
		cobro_pendiente.id_contrato = " . $contrato . " AND
		cobro.estado NOT IN ('PAGADO')";
		$query = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
		list($monto) = mysql_fetch_array($query);
		return empty($monto) ? 0 : $monto;
	}

	function MontoHitosPagados($contrato)
	{
		$sql = "SELECT SUM(cobro_pendiente.monto_estimado) AS monto 
		FROM cobro_pendiente
			JOIN cobro ON cobro_pendiente.id_cobro = cobro.id_cobro
		WHERE cobro_pendiente.id_cobro IS NOT NULL AND 
		cobro_pendiente.hito = 1 AND 
		cobro_pendiente.id_contrato = " . $contrato . " AND
		cobro.estado IN ('PAGADO')";
		$query = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
		list($monto) = mysql_fetch_array($query);
		return empty($monto) ? 0 : $monto;
	}
	
	function ObtenerHitosCumplidosParaCorreos() {
		$sql = "SELECT
			cobro_pendiente.id_cobro_pendiente,
			cobro_pendiente.descripcion,
			cobro_pendiente.monto_estimado,
			cobro_pendiente.id_contrato,
			contrato.id_usuario_responsable,
			contrato.id_usuario_secundario,
			prm_moneda.simbolo,
			prm_moneda.cifras_decimales,
			cliente.glosa_cliente,
			cliente.codigo_cliente,
			cobro_pendiente.fecha_cobro,
			GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ', ') as asuntos
		FROM 
			cobro_pendiente 
			LEFT JOIN contrato ON cobro_pendiente.id_contrato = contrato.id_contrato
			LEFT JOIN usuario ON contrato.id_usuario_responsable = usuario.id_usuario
			LEFT JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
			LEFT JOIN cliente ON contrato.codigo_cliente = cliente.codigo_cliente
			LEFT JOIN asunto ON contrato.id_contrato = asunto.id_contrato
		WHERE 
			cobro_pendiente.hito = 1 AND
			cobro_pendiente.fecha_cobro IS NOT NULL AND
			cobro_pendiente.fecha_cobro <= NOW() AND
			cobro_pendiente.id_cobro IS NULL AND
			(contrato.id_usuario_responsable IS NOT NULL OR contrato.id_usuario_secundario IS NOT NULL)
		GROUP BY cobro_pendiente.id_cobro_pendiente
		";

		$query = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);

		$cliente_hitos = array(); //la estructura es usuario->cliente->contrato->(asunto,detalles,lista_hitos)
		while ($hito = mysql_fetch_array($query)) {
			//Monto con simbolo
			$idioma = new Objeto($this->sesion, '', '', 'prm_idioma', 'codigo_idioma');
			$idioma->Load(strtolower(UtilesApp::GetConf($this->sesion, 'Idioma')));
			$monto_hito_simbolo = $hito['simbolo'] . " " . number_format($hito['monto_estimado'], $hito['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

			//Responsables
			$usuarios_responsable = array();
			if (!empty($hito['id_usuario_responsable'])) {
				$usuarios_responsable[$hito['id_usuario_responsable']] = $hito['id_usuario_responsable'];
			}
			if (!empty($hito['id_usuario_secundario'])) {
				$usuarios_responsable[$hito['id_usuario_secundario']] = $hito['id_usuario_secundario'];
			}
			if (empty($usuarios_responsable)) {
				continue;
			}

			foreach ($usuarios_responsable as $usuario_responsable) {
				if (!isset($cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['cliente'])) {
					$cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['cliente'] = array("glosa_cliente" => $hito['glosa_cliente']);
				}
				if (!isset($cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['contratos'][$hito['id_contrato']])) {
					$cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['contratos'][$hito['id_contrato']] = array(
						"asuntos" => $hito['asuntos'],
						"monto_por_liquidar" => $this->MontoHitosPorLiquidar($hito['id_contrato']),
						"monto_liquidado" => $this->MontoHitosLiquidados($hito['id_contrato']),
						"pagado" => $this->MontoHitosPagados($hito['id_contrato'])
					);
				}
				$cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['contratos'][$hito['id_contrato']]['hitos'][] = array(
					'descripcion' => $hito['descripcion'],
					'monto_estimado' => $monto_hito_simbolo,
					'fecha_cobro' => $hito['fecha_cobro']
				);
			}
			
			$sql = "UPDATE cobro_pendiente SET fecha_cobro = NULL WHERE hito = 1 AND id_cobro_pendiente = " . $hito['id_cobro_pendiente'];
			mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
		}

		return $cliente_hitos;
	}
}

class ListaCobrosPendientes extends Lista
{
    function ListaCobrosPendientes($sesion, $params, $query)
    {
        $this->Lista($sesion, 'CobroPendiente', $params, $query);
    }
}
